<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatusEnum;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Requests\UpdateLeaveRequestRequest;
use App\Models\InstitutionPerson;
use App\Models\LeaveDocument;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Services\ApproverResolver;
use App\Services\CurrentStaffResolver;
use App\Services\LeaveBalanceService;
use App\Services\LeaveConflictService;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveEligibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function __construct(
        private CurrentStaffResolver $staffResolver,
        private LeaveBalanceService $balance,
        private LeaveEligibilityService $eligibility,
        private LeaveConflictService $conflicts,
        private LeaveDayCalculator $calculator,
        private ApproverResolver $approverResolver,
    ) {}

    public function index(): Response
    {
        $staff = $this->currentStaff();
        $activeYear = LeaveYear::query()->where('is_active', true)->first();

        return Inertia::render('LeaveRequest/Index', [
            'requests' => LeaveRequest::query()
                ->where('staff_id', $staff->id)
                ->with('leaveType')
                ->when(request()->status, fn ($query, $status) => $query->where('status', $status))
                ->latest('id')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveRequest $leaveRequest): array => $this->summary($leaveRequest)),
            'statuses' => collect(LeaveRequestStatusEnum::cases())
                ->map(fn (LeaveRequestStatusEnum $status): array => ['value' => $status->value, 'label' => $status->label()]),
            'balance' => $activeYear ? $this->balance->ledger($staff, $activeYear) : [],
            'filters' => request()->only('status'),
        ]);
    }

    public function create(): Response
    {
        $staff = $this->currentStaff();

        return Inertia::render('LeaveRequest/Create', $this->formOptions($staff));
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $staff = $this->currentStaff();
        $leaveType = LeaveType::findOrFail($request->integer('leave_type_id'));
        $start = Carbon::parse($request->date('start_date'));
        $end = Carbon::parse($request->date('end_date'));
        $year = $this->resolveYear($start, $end);
        $requestedDays = $this->computeDays($leaveType, $year, $start, $end);

        $planItem = $this->resolvePlanItem($request->input('leave_plan_item_id'), $staff);

        $this->guardRequest($staff, $leaveType, $year, $start, $end, $requestedDays, $request->hasFile('file_name'), $request->input('relieving_officer_id'));

        $approver = $this->approverResolver->resolve($staff);

        $leaveRequest = DB::transaction(function () use ($request, $staff, $leaveType, $year, $start, $end, $requestedDays, $planItem, $approver) {
            $leaveRequest = LeaveRequest::create([
                'staff_id' => $staff->id,
                'leave_type_id' => $leaveType->id,
                'leave_year_id' => $year->id,
                'leave_plan_item_id' => $planItem?->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'requested_days' => $requestedDays,
                'reason' => $request->input('reason'),
                'address_during_leave' => $request->input('address_during_leave'),
                'contact_during_leave' => $request->input('contact_during_leave'),
                'relieving_officer_id' => $request->input('relieving_officer_id'),
                'approver_id' => $approver?->id,
                'status' => LeaveRequestStatusEnum::Pending,
            ]);

            $this->storeDocuments($request, $leaveRequest);
            $this->logStatus($leaveRequest, null, LeaveRequestStatusEnum::Pending, 'submitted');

            if ($planItem) {
                $planItem->update(['converted_request_id' => $leaveRequest->id]);
            }

            return $leaveRequest;
        });

        $recipients = $approver
            ? User::where('person_id', $approver->person_id)->get()
            : User::permission('approve staff leave')->get();
        Notification::send($recipients, new LeaveRequestSubmittedNotification($leaveRequest));

        return redirect()->route('leave-request.index')->with('success', 'Leave request submitted.');
    }

    public function show(LeaveRequest $leaveRequest): Response
    {
        $this->authorize('view', $leaveRequest);
        $leaveRequest->load(['leaveType', 'leaveYear', 'relievingOfficer.person', 'approver.person', 'documents', 'statusHistories.changedBy']);

        return Inertia::render('LeaveRequest/Show', [
            'request' => array_merge($this->summary($leaveRequest), [
                'reason' => $leaveRequest->reason,
                'address_during_leave' => $leaveRequest->address_during_leave,
                'contact_during_leave' => $leaveRequest->contact_during_leave,
                'relieving_officer' => $leaveRequest->relievingOfficer?->person?->full_name,
                'approver' => $leaveRequest->approver?->person?->full_name,
                'approved_days' => $leaveRequest->approved_days,
                'decline_reason' => $leaveRequest->decline_reason,
                'documents' => $leaveRequest->documents->map(fn (LeaveDocument $document): array => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'file_type' => $document->file_type,
                ]),
                'history' => $leaveRequest->statusHistories->map(fn ($history): array => [
                    'from' => $history->from_status,
                    'to' => $history->to_status,
                    'reason' => $history->reason,
                    'by' => $history->changedBy?->name,
                    'at' => $history->created_at?->format('Y-m-d H:i'),
                ]),
                'can_edit' => $leaveRequest->status === LeaveRequestStatusEnum::Pending,
            ]),
        ]);
    }

    public function edit(LeaveRequest $leaveRequest): Response
    {
        $this->authorize('update', $leaveRequest);
        $this->ensurePending($leaveRequest);
        $staff = $this->currentStaff();
        $leaveRequest->load('documents');

        return Inertia::render('LeaveRequest/Edit', array_merge($this->formOptions($staff), [
            'request' => array_merge($this->summary($leaveRequest), [
                'reason' => $leaveRequest->reason,
                'address_during_leave' => $leaveRequest->address_during_leave,
                'contact_during_leave' => $leaveRequest->contact_during_leave,
                'relieving_officer_id' => $leaveRequest->relieving_officer_id,
            ]),
        ]));
    }

    public function update(UpdateLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('update', $leaveRequest);
        $this->ensurePending($leaveRequest);

        $staff = $this->currentStaff();
        $leaveType = LeaveType::findOrFail($request->integer('leave_type_id'));
        $start = Carbon::parse($request->date('start_date'));
        $end = Carbon::parse($request->date('end_date'));
        $year = $this->resolveYear($start, $end);
        $requestedDays = $this->computeDays($leaveType, $year, $start, $end);

        $hasEvidence = $request->hasFile('file_name') || $leaveRequest->documents()->exists();
        $this->guardRequest($staff, $leaveType, $year, $start, $end, $requestedDays, $hasEvidence, $request->input('relieving_officer_id'), $leaveRequest->id);

        DB::transaction(function () use ($request, $leaveRequest, $leaveType, $year, $start, $end, $requestedDays) {
            $leaveRequest->update([
                'leave_type_id' => $leaveType->id,
                'leave_year_id' => $year->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'requested_days' => $requestedDays,
                'reason' => $request->input('reason'),
                'address_during_leave' => $request->input('address_during_leave'),
                'contact_during_leave' => $request->input('contact_during_leave'),
                'relieving_officer_id' => $request->input('relieving_officer_id'),
            ]);

            $this->storeDocuments($request, $leaveRequest);
        });

        return redirect()->route('leave-request.show', $leaveRequest)->with('success', 'Leave request updated.');
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('cancel', $leaveRequest);

        if ($leaveRequest->status === LeaveRequestStatusEnum::Cancelled) {
            return redirect()->back()->with('info', 'Request already cancelled.');
        }

        $from = $leaveRequest->status;
        $leaveRequest->update(['status' => LeaveRequestStatusEnum::Cancelled]);
        $this->logStatus($leaveRequest, $from, LeaveRequestStatusEnum::Cancelled, 'cancelled by staff');

        return redirect()->route('leave-request.index')->with('success', 'Leave request cancelled.');
    }

    public function previewDays(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $year = LeaveYear::query()
            ->whereDate('start_date', '<=', $start->toDateString())
            ->whereDate('end_date', '>=', $end->toDateString())
            ->first();

        return response()->json(['days' => $this->computeDays($leaveType, $year, $start, $end)]);
    }

    public function relievingOfficerOptions(): JsonResponse
    {
        $staff = $this->currentStaff();

        $options = InstitutionPerson::query()
            ->active()
            ->where('id', '!=', $staff->id)
            ->with('person')
            ->get()
            ->map(fn (InstitutionPerson $person): array => [
                'value' => $person->id,
                'label' => trim(($person->person?->full_name ?? 'Staff') . ' — ' . $person->staff_number),
            ])
            ->values();

        return response()->json($options);
    }

    public function downloadDocument(LeaveRequest $leaveRequest, LeaveDocument $document): StreamedResponse
    {
        $this->authorize('view', $leaveRequest);
        abort_unless($document->leave_request_id === $leaveRequest->id, 404);
        abort_unless(Storage::disk('leave-documents')->exists($document->file_name), 404, 'File not found.');

        return Storage::disk('leave-documents')->download($document->file_name, $document->title ?? 'evidence');
    }

    public function destroyDocument(LeaveRequest $leaveRequest, LeaveDocument $document): RedirectResponse
    {
        $this->authorize('update', $leaveRequest);
        $this->ensurePending($leaveRequest);
        abort_unless($document->leave_request_id === $leaveRequest->id, 404);

        if ($document->file_name && Storage::disk('leave-documents')->exists($document->file_name)) {
            Storage::disk('leave-documents')->delete($document->file_name);
        }
        $document->delete();

        return redirect()->back()->with('success', 'Evidence removed.');
    }

    private function currentStaff(): InstitutionPerson
    {
        return $this->staffResolver->resolveOrAbort(request()->user());
    }

    private function resolveYear(Carbon $start, Carbon $end): LeaveYear
    {
        $year = LeaveYear::query()
            ->whereDate('start_date', '<=', $start->toDateString())
            ->whereDate('end_date', '>=', $end->toDateString())
            ->first();

        if (! $year) {
            throw ValidationException::withMessages([
                'start_date' => 'The selected dates do not fall within a single configured leave year.',
            ]);
        }

        return $year;
    }

    private function resolvePlanItem(mixed $planItemId, InstitutionPerson $staff): ?LeavePlanItem
    {
        if (! $planItemId) {
            return null;
        }

        $item = LeavePlanItem::query()
            ->whereKey($planItemId)
            ->whereNull('converted_request_id')
            ->whereHas('leavePlan', fn ($query) => $query->where('staff_id', $staff->id))
            ->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'leave_plan_item_id' => 'That plan item is not available to convert.',
            ]);
        }

        return $item;
    }

    private function computeDays(LeaveType $leaveType, ?LeaveYear $year, Carbon $start, Carbon $end): int
    {
        return $this->calculator->calculateDays($leaveType, $start, $end, $this->balance->holidayDates($year));
    }

    private function guardRequest(InstitutionPerson $staff, LeaveType $leaveType, LeaveYear $year, Carbon $start, Carbon $end, int $requestedDays, bool $hasEvidence, mixed $relievingOfficerId, ?int $ignoreId = null): void
    {
        $failures = $this->eligibility->failures($staff, $leaveType, $year);
        if ($failures !== []) {
            throw ValidationException::withMessages(['leave_type_id' => $failures[0]]);
        }

        if ($requestedDays < 1) {
            throw ValidationException::withMessages(['start_date' => 'The selected range contains no countable leave days.']);
        }

        if ($leaveType->min_notice_days > 0 && $start->lessThan(now()->startOfDay()->addDays($leaveType->min_notice_days))) {
            throw ValidationException::withMessages(['start_date' => $leaveType->name . ' requires at least ' . $leaveType->min_notice_days . ' day(s) notice.']);
        }

        if ($leaveType->max_consecutive_days && $requestedDays > $leaveType->max_consecutive_days) {
            throw ValidationException::withMessages(['end_date' => $leaveType->name . ' allows at most ' . $leaveType->max_consecutive_days . ' consecutive day(s).']);
        }

        if ((int) $relievingOfficerId === $staff->id) {
            throw ValidationException::withMessages(['relieving_officer_id' => 'You cannot be your own relieving officer.']);
        }

        if ($this->conflicts->overlaps($staff, $start, $end, $ignoreId)) {
            throw ValidationException::withMessages(['start_date' => 'This range overlaps another leave request.']);
        }

        if ($leaveType->requires_evidence && ! $hasEvidence) {
            throw ValidationException::withMessages(['file_name' => $leaveType->name . ' requires supporting evidence.']);
        }

        $remaining = $this->balance->remainingForRequest($staff, $leaveType->id, $year, $ignoreId);
        if ($requestedDays > $remaining) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'This exceeds your remaining ' . $leaveType->name . ' balance (' . $remaining . ' day(s) left).',
            ]);
        }
    }

    private function storeDocuments(Request $request, LeaveRequest $leaveRequest): void
    {
        if (! $request->hasFile('file_name')) {
            return;
        }

        $titles = (array) $request->input('document_title', []);

        foreach ($request->file('file_name') as $i => $file) {
            $path = Storage::disk('leave-documents')->put('/', $file);
            $leaveRequest->documents()->create([
                'title' => $titles[$i] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_name' => $path,
                'file_type' => $file->getMimeType(),
            ]);
        }
    }

    private function logStatus(LeaveRequest $leaveRequest, ?LeaveRequestStatusEnum $from, LeaveRequestStatusEnum $to, ?string $reason = null): void
    {
        $leaveRequest->statusHistories()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => request()->user()?->id,
            'reason' => $reason,
        ]);
    }

    private function ensurePending(LeaveRequest $leaveRequest): void
    {
        abort_unless($leaveRequest->status === LeaveRequestStatusEnum::Pending, 403, 'Only pending requests can be modified.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(LeaveRequest $leaveRequest): array
    {
        return [
            'id' => $leaveRequest->id,
            'leave_type_id' => $leaveRequest->leave_type_id,
            'leave_type' => $leaveRequest->leaveType?->name,
            'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
            'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
            'requested_days' => $leaveRequest->requested_days,
            'status' => $leaveRequest->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(InstitutionPerson $staff): array
    {
        $activeYear = LeaveYear::query()->where('is_active', true)->first();

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $type): array => [
                'value' => $type->id,
                'label' => $type->name,
                'requires_evidence' => $type->requires_evidence,
                'remaining' => $activeYear ? $this->balance->remainingForRequest($staff, $type->id, $activeYear) : null,
            ])
            ->values();

        $planItems = LeavePlanItem::query()
            ->whereNull('converted_request_id')
            ->whereHas('leavePlan', fn ($query) => $query->where('staff_id', $staff->id))
            ->with('leaveType')
            ->get()
            ->map(fn (LeavePlanItem $item): array => [
                'value' => $item->id,
                'label' => ($item->leaveType?->name ?? 'Leave') . ': ' . $item->start_date?->format('Y-m-d') . ' → ' . $item->end_date?->format('Y-m-d'),
                'leave_type_id' => $item->leave_type_id,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
            ])
            ->values();

        return [
            'leaveTypes' => $leaveTypes,
            'planItems' => $planItems,
        ];
    }
}
