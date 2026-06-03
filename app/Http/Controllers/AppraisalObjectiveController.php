<?php

namespace App\Http\Controllers;

use App\Enums\AppraisalStatusEnum;
use App\Http\Requests\StoreAppraisalObjectiveRequest;
use App\Http\Requests\UpdateAppraisalObjectiveRequest;
use App\Models\Appraisal;
use App\Models\AppraisalObjective;
use App\Models\User;
use App\Services\Appraisal\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppraisalObjectiveController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function store(StoreAppraisalObjectiveRequest $request, Appraisal $appraisal)
    {
        $this->ensureCanEditObjectives($appraisal, $request->user());

        $appraisal->objectives()->create($request->validated());

        return redirect()->back()->with('success', 'Objective added.');
    }

    public function update(UpdateAppraisalObjectiveRequest $request, Appraisal $appraisal, AppraisalObjective $objective)
    {
        $this->ensureObjectiveBelongs($appraisal, $objective);
        $this->ensureCanEditObjectives($appraisal, $request->user());

        $objective->update($request->validated());

        return redirect()->back()->with('success', 'Objective updated.');
    }

    public function delete(Request $request, Appraisal $appraisal, AppraisalObjective $objective)
    {
        $this->ensureObjectiveBelongs($appraisal, $objective);
        $this->ensureCanEditObjectives($appraisal, $request->user());

        $objective->delete();

        return redirect()->back()->with('success', 'Objective removed.');
    }

    /**
     * Staff submits the agreed objectives to the appraiser for sign-off.
     */
    public function submit(Request $request, Appraisal $appraisal)
    {
        abort_unless(
            $appraisal->isOwnedBy($request->user()) || Gate::allows('edit appraisal'),
            403,
            'You cannot submit objectives for this appraisal.',
        );
        $this->ensureDraftObjectives($appraisal);
        $this->ensureWeightsTotal100($appraisal);

        $this->service->notifyStaff(
            $appraisal->appraiser_id,
            $appraisal,
            'agree_objectives',
            ($appraisal->staff?->person?->full_name ?? 'A staff member') . ' submitted objectives for your agreement.',
        );

        return redirect()->back()->with('success', 'Objectives submitted to your appraiser.');
    }

    /**
     * Appraiser agrees the objectives, moving the appraisal forward.
     */
    public function agree(Request $request, Appraisal $appraisal)
    {
        abort_unless(
            (Gate::allows('review appraisal') && $appraisal->isAppraiserUser($request->user())) || Gate::allows('edit appraisal'),
            403,
            'You cannot agree objectives for this appraisal.',
        );
        $this->ensureDraftObjectives($appraisal);
        $this->ensureWeightsTotal100($appraisal);

        $this->service->transition($appraisal, AppraisalStatusEnum::ObjectivesAgreed, $request->user(), $request->input('comment'));

        $this->service->notifyStaff(
            $appraisal->staff_id,
            $appraisal,
            'objectives_agreed',
            'Your objectives have been agreed by your appraiser.',
        );

        return redirect()->back()->with('success', 'Objectives agreed.');
    }

    protected function ensureObjectiveBelongs(Appraisal $appraisal, AppraisalObjective $objective): void
    {
        abort_unless($objective->appraisal_id === $appraisal->id, 404);
    }

    protected function ensureCanEditObjectives(Appraisal $appraisal, User $user): void
    {
        $this->ensureDraftObjectives($appraisal);

        $allowed = Gate::allows('edit appraisal')
            || Gate::allows('create appraisal')
            || (Gate::allows('submit self appraisal') && $appraisal->isOwnedBy($user));

        abort_unless($allowed, 403, 'You cannot edit objectives for this appraisal.');
    }

    protected function ensureDraftObjectives(Appraisal $appraisal): void
    {
        abort_unless(
            $appraisal->status === AppraisalStatusEnum::DraftObjectives,
            422,
            'Objectives can only be changed before they are agreed.',
        );
    }

    protected function ensureWeightsTotal100(Appraisal $appraisal): void
    {
        $total = (int) $appraisal->objectives()->sum('weight');

        if ($total !== 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'objectives' => "Objective weights must sum to 100 (currently {$total}).",
            ]);
        }
    }
}
