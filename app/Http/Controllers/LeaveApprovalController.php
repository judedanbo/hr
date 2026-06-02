<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatusEnum;
use App\Http\Requests\ApproveLeaveRequestRequest;
use App\Http\Requests\DeclineLeaveRequestRequest;
use App\Models\ApprovalDelegation;
use App\Models\InstitutionPerson;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestDecidedNotification;
use App\Services\LeaveBalanceService;
use App\Services\LeaveCoverageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LeaveApprovalController extends Controller
{
    public function __construct(
        private LeaveBalanceService $balance,
        private LeaveCoverageService $coverage,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $staffIds = $this->myStaffIds($user);
        $isPool = $user->can('approve staff leave');

        $delegatorIds = ApprovalDelegation::query()
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereIn('delegate_id', $staffIds)
            ->pluck('delegator_id');

        $requests = LeaveRequest::query()
            ->where('status', LeaveRequestStatusEnum::Pending)
            ->with(['staff.person', 'leaveType', 'leaveYear'])
            ->when(! $isPool, fn ($query) => $query->where(function ($query) use ($staffIds, $delegatorIds): void {
                $query->whereIn('approver_id', $staffIds)
                    ->orWhereIn('approver_id', $delegatorIds);
            }))
            ->latest('id')
            ->paginate()
            ->withQueryString()
            ->through(fn (LeaveRequest $leaveRequest): array => [
                'id' => $leaveRequest->id,
                'staff' => $leaveRequest->staff?->person?->full_name,
                'leave_type' => $leaveRequest->leaveType?->name,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                'requested_days' => $leaveRequest->requested_days,
                'remaining' => $this->remainingFor($leaveRequest),
            ]);

        return Inertia::render('LeaveApproval/Index', ['requests' => $requests]);
    }

    public function approve(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decide', $leaveRequest);
        $this->ensurePending($leaveRequest);

        $approvedDays = (int) ($request->input('approved_days') ?? $leaveRequest->requested_days);

        if ($approvedDays < 1 || $approvedDays > $leaveRequest->requested_days) {
            throw ValidationException::withMessages([
                'approved_days' => 'Approved days must be between 1 and the ' . $leaveRequest->requested_days . ' requested.',
            ]);
        }

        DB::transaction(function () use ($leaveRequest, $approvedDays, $request) {
            // Lock the staff record first so concurrent approvals for the same
            // person serialize on a shared row. Locking only the request being
            // approved would let two separate pending requests for one staff
            // member each pass the balance re-check and both commit, over-drawing
            // the entitlement; the shared lock forces the second approval to read
            // the first one's committed result.
            InstitutionPerson::query()->whereKey($leaveRequest->staff_id)->lockForUpdate()->first();

            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->first();
            $this->ensurePending($locked);

            $assigned = $this->balance->assignedDays($locked->staff, $locked->leave_type_id, $locked->leaveYear);
            $taken = $this->balance->takenDays($locked->staff, $locked->leave_type_id, $locked->leaveYear, $locked->id);

            if ($taken + $approvedDays > $assigned) {
                throw ValidationException::withMessages([
                    'approved_days' => 'Approving ' . $approvedDays . ' day(s) would exceed the ' . $assigned . '-day entitlement (' . $taken . ' already approved).',
                ]);
            }

            if ($this->coverage->exceedsLimit($locked->staff, $locked->leaveType, $locked->start_date, $locked->end_date, $locked->id)) {
                throw ValidationException::withMessages([
                    'coverage' => 'The unit already has the maximum number of staff on ' . $locked->leaveType->name . ' leave for these dates.',
                ]);
            }

            $locked->update([
                'status' => LeaveRequestStatusEnum::Approved,
                'approved_days' => $approvedDays,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            $reduced = $approvedDays < $locked->requested_days;
            $this->logStatus($locked, LeaveRequestStatusEnum::Pending, LeaveRequestStatusEnum::Approved, $reduced ? 'reduced' : 'approved');
            $this->notifyRequester($locked, $reduced ? 'Reduced' : 'Approved');
        });

        return redirect()->route('leave-approvals.index')->with('success', 'Leave request approved.');
    }

    public function decline(DeclineLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('decide', $leaveRequest);
        $this->ensurePending($leaveRequest);

        $leaveRequest->update([
            'status' => LeaveRequestStatusEnum::Declined,
            'decline_reason' => $request->input('decline_reason'),
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        $this->logStatus($leaveRequest, LeaveRequestStatusEnum::Pending, LeaveRequestStatusEnum::Declined, $request->input('decline_reason'));
        $this->notifyRequester($leaveRequest, 'Declined');

        return redirect()->route('leave-approvals.index')->with('success', 'Leave request declined.');
    }

    public function reassign(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(Gate::allows('reassign leave approver'), 403);
        $this->ensurePending($leaveRequest);

        $data = $request->validate([
            'approver_id' => ['required', 'integer', 'exists:institution_person,id'],
        ]);

        if ((int) $data['approver_id'] === $leaveRequest->staff_id) {
            throw ValidationException::withMessages([
                'approver_id' => 'A request cannot be reassigned to its own requester.',
            ]);
        }

        $leaveRequest->update(['approver_id' => $data['approver_id']]);

        return redirect()->back()->with('success', 'Approver reassigned.');
    }

    /**
     * @return Collection<int, int>
     */
    private function myStaffIds(User $user): Collection
    {
        if (! $user->person_id) {
            return collect();
        }

        return InstitutionPerson::query()->where('person_id', $user->person_id)->pluck('id');
    }

    private function remainingFor(LeaveRequest $leaveRequest): ?int
    {
        if (! $leaveRequest->staff || ! $leaveRequest->leaveYear) {
            return null;
        }

        $assigned = $this->balance->assignedDays($leaveRequest->staff, $leaveRequest->leave_type_id, $leaveRequest->leaveYear);

        return $assigned - $this->balance->takenDays($leaveRequest->staff, $leaveRequest->leave_type_id, $leaveRequest->leaveYear);
    }

    private function ensurePending(LeaveRequest $leaveRequest): void
    {
        abort_unless($leaveRequest->status === LeaveRequestStatusEnum::Pending, 403, 'Only pending requests can be decided.');
    }

    private function logStatus(LeaveRequest $leaveRequest, LeaveRequestStatusEnum $from, LeaveRequestStatusEnum $to, ?string $reason): void
    {
        $leaveRequest->statusHistories()->create([
            'from_status' => $from->value,
            'to_status' => $to->value,
            'changed_by' => request()->user()?->id,
            'reason' => $reason,
        ]);
    }

    private function notifyRequester(LeaveRequest $leaveRequest, string $outcome): void
    {
        $personId = $leaveRequest->staff?->person_id;
        if (! $personId) {
            return;
        }

        Notification::send(
            User::where('person_id', $personId)->get(),
            new LeaveRequestDecidedNotification($leaveRequest, $outcome),
        );
    }
}
