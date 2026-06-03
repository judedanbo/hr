<?php

namespace App\Http\Controllers;

use App\Enums\LeavePlanStatusEnum;
use App\Http\Requests\StoreLeavePlanItemRequest;
use App\Http\Requests\UpdateLeavePlanItemRequest;
use App\Models\InstitutionPerson;
use App\Models\LeavePlan;
use App\Models\LeavePlanItem;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use App\Notifications\LeavePlanSubmittedNotification;
use App\Services\CurrentStaffResolver;
use App\Services\LeaveBalanceService;
use App\Services\LeaveDayCalculator;
use App\Services\LeavePlanningWindowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LeavePlanController extends Controller
{
    public function __construct(
        private LeavePlanningWindowService $windows,
        private LeaveBalanceService $balance,
        private LeaveDayCalculator $calculator,
        private CurrentStaffResolver $staffResolver,
    ) {}

    public function index(): Response
    {
        $staff = $this->currentStaff();
        $window = $this->windows->openWindow();

        $year = $window?->leaveYear;
        $plan = null;

        if ($window) {
            $plan = LeavePlan::firstOrCreate(
                ['staff_id' => $staff->id, 'leave_year_id' => $year->id],
                ['status' => LeavePlanStatusEnum::Draft],
            );
        } else {
            $plan = LeavePlan::query()
                ->where('staff_id', $staff->id)
                ->with('leaveYear')
                ->latest('id')
                ->first();
            $year = $plan?->leaveYear;
        }

        $plan?->load(['items.leaveType']);

        return Inertia::render('LeavePlan/Index', [
            'windowOpen' => (bool) $window,
            'window' => $window ? [
                'closes_at' => $window->closes_at?->format('Y-m-d H:i'),
                'instructions' => $window->instructions,
                'require_full_plan' => $window->require_full_plan,
            ] : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'status' => $plan->status->value,
                'submitted_at' => $plan->submitted_at?->format('Y-m-d H:i'),
                'year' => $year?->year,
            ] : null,
            'items' => $plan ? $plan->items->map(fn (LeavePlanItem $item): array => [
                'id' => $item->id,
                'leave_type_id' => $item->leave_type_id,
                'leave_type' => $item->leaveType?->name,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'proposed_days' => $item->proposed_days,
                'note' => $item->note,
            ])->values() : [],
            'ledger' => $year ? $this->ledger($staff, $year) : [],
            'leaveTypes' => $year ? $this->plannableTypes($staff, $year) : [],
        ]);
    }

    public function storeItem(StoreLeavePlanItemRequest $request): RedirectResponse
    {
        $staff = $this->currentStaff();
        $window = $this->openWindowOrFail();
        $year = $window->leaveYear;

        $plan = LeavePlan::firstOrCreate(
            ['staff_id' => $staff->id, 'leave_year_id' => $year->id],
            ['status' => LeavePlanStatusEnum::Draft],
        );

        $leaveType = LeaveType::findOrFail($request->integer('leave_type_id'));
        $start = Carbon::parse($request->date('start_date'));
        $end = Carbon::parse($request->date('end_date'));
        $proposedDays = $this->computeDays($leaveType, $year, $start, $end);

        $this->guardItem($staff, $year, $plan, $leaveType, $start, $end, $proposedDays);

        $plan->items()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'proposed_days' => $proposedDays,
            'note' => $request->input('note'),
        ]);

        return redirect()->route('leave-plan.index')->with('success', 'Plan item added.');
    }

    public function updateItem(UpdateLeavePlanItemRequest $request, LeavePlanItem $item): RedirectResponse
    {
        $staff = $this->currentStaff();
        $this->authorizeItem($item, $staff);
        $window = $this->openWindowOrFail();
        $year = $window->leaveYear;
        $plan = $item->leavePlan;

        $leaveType = LeaveType::findOrFail($request->integer('leave_type_id'));
        $start = Carbon::parse($request->date('start_date'));
        $end = Carbon::parse($request->date('end_date'));
        $proposedDays = $this->computeDays($leaveType, $year, $start, $end);

        $this->guardItem($staff, $year, $plan, $leaveType, $start, $end, $proposedDays, $item->id);

        $item->update([
            'leave_type_id' => $leaveType->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'proposed_days' => $proposedDays,
            'note' => $request->input('note'),
        ]);

        return redirect()->route('leave-plan.index')->with('success', 'Plan item updated.');
    }

    public function destroyItem(LeavePlanItem $item): RedirectResponse
    {
        $staff = $this->currentStaff();
        $this->authorizeItem($item, $staff);
        $this->openWindowOrFail();

        $item->delete();

        return redirect()->route('leave-plan.index')->with('success', 'Plan item removed.');
    }

    public function submit(): RedirectResponse
    {
        $staff = $this->currentStaff();
        $window = $this->openWindowOrFail();
        $year = $window->leaveYear;

        $plan = LeavePlan::firstOrCreate(
            ['staff_id' => $staff->id, 'leave_year_id' => $year->id],
            ['status' => LeavePlanStatusEnum::Draft],
        );

        if ($window->require_full_plan) {
            foreach ($this->ledger($staff, $year) as $row) {
                if ($row['planned'] < $row['assigned']) {
                    throw ValidationException::withMessages([
                        'plan' => 'You must plan all assigned days before submitting (' . $row['leave_type'] . ' is under-planned).',
                    ]);
                }
            }
        }

        $plan->update([
            'status' => LeavePlanStatusEnum::Submitted,
            'submitted_at' => now(),
        ]);

        Notification::send(
            User::permission('view all leave plans')->get(),
            new LeavePlanSubmittedNotification($plan),
        );

        return redirect()->route('leave-plan.index')->with('success', 'Leave plan submitted.');
    }

    public function previewDays(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $window = $this->windows->openWindow();
        $year = $window?->leaveYear;

        $days = $this->computeDays(
            $leaveType,
            $year,
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
        );

        return response()->json(['days' => $days]);
    }

    private function currentStaff(): InstitutionPerson
    {
        return $this->staffResolver->resolveOrAbort(request()->user());
    }

    private function openWindowOrFail(): \App\Models\LeavePlanningWindow
    {
        $window = $this->windows->openWindow();

        if (! $window) {
            throw ValidationException::withMessages([
                'plan' => 'The leave planning window is not currently open.',
            ]);
        }

        return $window->loadMissing('leaveYear');
    }

    private function authorizeItem(LeavePlanItem $item, InstitutionPerson $staff): void
    {
        abort_unless($item->leavePlan->staff_id === $staff->id, 403);
    }

    private function computeDays(LeaveType $leaveType, ?LeaveYear $year, Carbon $start, Carbon $end): int
    {
        return $this->calculator->calculateDays($leaveType, $start, $end, $this->balance->holidayDates($year));
    }

    private function guardItem(InstitutionPerson $staff, LeaveYear $year, LeavePlan $plan, LeaveType $leaveType, Carbon $start, Carbon $end, int $proposedDays, ?int $ignoreItemId = null): void
    {
        if ($start->lessThan($year->start_date) || $end->greaterThan($year->end_date)) {
            throw ValidationException::withMessages([
                'start_date' => 'Dates must fall within the ' . $year->year . ' leave year.',
            ]);
        }

        if ($proposedDays < 1) {
            throw ValidationException::withMessages([
                'start_date' => 'The selected range contains no countable leave days.',
            ]);
        }

        $overlaps = $plan->items()
            ->when($ignoreItemId, fn ($query) => $query->where('id', '!=', $ignoreItemId))
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'This range overlaps another item in your plan.',
            ]);
        }

        if ($leaveType->gender_restriction && $staff->person?->gender !== $leaveType->gender_restriction) {
            throw ValidationException::withMessages([
                'leave_type_id' => $leaveType->name . ' is restricted to ' . $leaveType->gender_restriction->label() . ' staff.',
            ]);
        }

        $assigned = $this->balance->assignedDays($staff, $leaveType->id, $year);

        if ($assigned < 1) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'You have no ' . $leaveType->name . ' entitlement for ' . $year->year . '.',
            ]);
        }

        // Only subtract the edited item's days when it is the same leave type
        // being validated (otherwise it is not part of this type's planned total).
        $alreadyPlanned = $this->balance->plannedDays($staff, $leaveType->id, $year)
            - ($ignoreItemId ? (int) $plan->items()->whereKey($ignoreItemId)->where('leave_type_id', $leaveType->id)->value('proposed_days') : 0);

        if ($alreadyPlanned + $proposedDays > $assigned) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'This brings your planned ' . $leaveType->name . ' to ' . ($alreadyPlanned + $proposedDays) . ' of ' . $assigned . ' assigned days.',
            ]);
        }
    }

    /**
     * @return array<int, array{leave_type_id: int, leave_type: string, assigned: int, planned: int, unplanned: int}>
     */
    private function ledger(InstitutionPerson $staff, LeaveYear $year): array
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $type): array => [
                'leave_type_id' => $type->id,
                'leave_type' => $type->name,
                'assigned' => $this->balance->assignedDays($staff, $type->id, $year),
                'planned' => $this->balance->plannedDays($staff, $type->id, $year),
                'unplanned' => $this->balance->unplanned($staff, $type->id, $year),
            ])
            ->filter(fn (array $row): bool => $row['assigned'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function plannableTypes(InstitutionPerson $staff, LeaveYear $year): array
    {
        return collect($this->ledger($staff, $year))
            ->map(fn (array $row): array => ['value' => $row['leave_type_id'], 'label' => $row['leave_type']])
            ->all();
    }
}
