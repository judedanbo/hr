<?php

namespace App\Http\Controllers;

use App\Enums\AppraisalStatusEnum;
use App\Http\Requests\SubmitAppraisalScoresRequest;
use App\Models\Appraisal;
use App\Models\User;
use App\Services\Appraisal\AppraisalScoringService;
use App\Services\Appraisal\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppraisalWorkflowController extends Controller
{
    public function __construct(
        protected AppraisalService $service,
        protected AppraisalScoringService $scoring,
    ) {}

    public function midyearStart(Request $request, Appraisal $appraisal)
    {
        $this->ensureCanReview($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::ObjectivesAgreed]);

        $this->service->transition($appraisal, AppraisalStatusEnum::MidYearInProgress, $request->user());

        return back()->with('success', 'Mid-year review started.');
    }

    public function midyearSave(Request $request, Appraisal $appraisal)
    {
        abort_unless($this->canReview($request->user(), $appraisal) || $appraisal->isOwnedBy($request->user()), 403);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::MidYearInProgress]);

        $data = $request->validate([
            'objectives' => ['array'],
            'objectives.*.id' => ['required', 'integer'],
            'objectives.*.midyear_progress' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data['objectives'] ?? [] as $row) {
            $appraisal->objectives()->whereKey($row['id'])->update(['midyear_progress' => $row['midyear_progress'] ?? null]);
        }

        return back()->with('success', 'Mid-year progress saved.');
    }

    public function midyearComplete(Request $request, Appraisal $appraisal)
    {
        $this->ensureCanReview($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::MidYearInProgress]);

        $this->service->transition($appraisal, AppraisalStatusEnum::MidYearCompleted, $request->user());
        $this->service->notifyStaff($appraisal->staff_id, $appraisal, 'midyear_completed', 'Your mid-year review is complete.');

        return back()->with('success', 'Mid-year review completed.');
    }

    public function openSelfAppraisal(Request $request, Appraisal $appraisal)
    {
        $this->ensureCanReview($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::ObjectivesAgreed, AppraisalStatusEnum::MidYearCompleted]);

        $this->service->ensureCompetencyRatings($appraisal);
        $this->service->transition($appraisal, AppraisalStatusEnum::SelfAppraisal, $request->user());
        $this->service->notifyStaff($appraisal->staff_id, $appraisal, 'self_appraisal', 'Your self-appraisal is now open.');

        return back()->with('success', 'Self-appraisal opened.');
    }

    public function submitSelf(SubmitAppraisalScoresRequest $request, Appraisal $appraisal)
    {
        abort_unless($appraisal->isOwnedBy($request->user()) || Gate::allows('edit appraisal'), 403);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::SelfAppraisal]);

        $this->saveScores($appraisal, $request->validated(), 'self_score');
        $appraisal->update(['self_submitted_at' => now()]);

        $this->service->transition($appraisal, AppraisalStatusEnum::SupervisorReview, $request->user());
        $this->service->notifyStaff($appraisal->appraiser_id, $appraisal, 'review', ($appraisal->staff?->person?->full_name ?? 'A staff member') . ' submitted their self-appraisal.');

        return back()->with('success', 'Self-appraisal submitted.');
    }

    public function submitReview(SubmitAppraisalScoresRequest $request, Appraisal $appraisal)
    {
        $this->ensureCanReview($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::SupervisorReview]);

        $this->saveScores($appraisal, $request->validated(), 'supervisor_score');
        $this->scoring->apply($appraisal);

        $next = $appraisal->reviewer_id ? AppraisalStatusEnum::ReviewerReview : AppraisalStatusEnum::AwaitingAcknowledgement;
        $this->service->transition($appraisal, $next, $request->user(), $request->input('comment'));

        if ($appraisal->reviewer_id) {
            $this->service->notifyStaff($appraisal->reviewer_id, $appraisal, 'countersign', 'An appraisal is ready for your countersignature.');
        } else {
            $this->service->notifyStaff($appraisal->staff_id, $appraisal, 'acknowledge', 'Your appraisal is ready for acknowledgement.');
        }

        return back()->with('success', 'Supervisor review submitted.');
    }

    public function countersign(Request $request, Appraisal $appraisal)
    {
        $this->ensureCanCountersign($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::ReviewerReview]);

        $this->service->transition($appraisal, AppraisalStatusEnum::AwaitingAcknowledgement, $request->user(), $request->input('comment'));
        $this->service->notifyStaff($appraisal->staff_id, $appraisal, 'acknowledge', 'Your appraisal is ready for acknowledgement.');

        return back()->with('success', 'Appraisal countersigned.');
    }

    public function returnToSupervisor(Request $request, Appraisal $appraisal)
    {
        $this->ensureCanCountersign($request->user(), $appraisal);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::ReviewerReview]);

        $this->service->transition($appraisal, AppraisalStatusEnum::SupervisorReview, $request->user(), $request->input('comment'));
        $this->service->notifyStaff($appraisal->appraiser_id, $appraisal, 'review', 'The reviewer returned an appraisal for revision.');

        return back()->with('success', 'Appraisal returned to the supervisor.');
    }

    public function acknowledge(Request $request, Appraisal $appraisal)
    {
        abort_unless($appraisal->isOwnedBy($request->user()) || Gate::allows('edit appraisal'), 403);
        $this->ensureStatus($appraisal, [AppraisalStatusEnum::AwaitingAcknowledgement]);

        $appraisal->update(['acknowledged_at' => now()]);
        $this->service->transition($appraisal, AppraisalStatusEnum::Completed, $request->user());

        return back()->with('success', 'Appraisal acknowledged.');
    }

    public function reassign(Request $request, Appraisal $appraisal)
    {
        abort_unless(Gate::allows('edit appraisal'), 403);

        $data = $request->validate([
            'appraiser_id' => ['nullable', 'exists:institution_person,id'],
            'reviewer_id' => ['nullable', 'exists:institution_person,id'],
        ]);

        $appraisal->update($data);

        return back()->with('success', 'Appraisal approvers updated.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveScores(Appraisal $appraisal, array $data, string $field): void
    {
        foreach ($data['objectives'] ?? [] as $row) {
            $objective = $appraisal->objectives()->find($row['id']);
            if (! $objective) {
                continue;
            }
            $objective->{$field} = $row['score'] ?? null;
            if (! empty($row['comment'])) {
                $objective->comment = $row['comment'];
            }
            $objective->save();
        }

        foreach ($data['competencies'] ?? [] as $row) {
            $rating = $appraisal->competencyRatings()->find($row['id']);
            if (! $rating) {
                continue;
            }
            $rating->{$field} = $row['score'] ?? null;
            if (! empty($row['comment'])) {
                $rating->comment = $row['comment'];
            }
            $rating->save();
        }
    }

    protected function canReview(User $user, Appraisal $appraisal): bool
    {
        return (Gate::allows('review appraisal') && $appraisal->isAppraiserUser($user)) || Gate::allows('edit appraisal');
    }

    protected function ensureCanReview(User $user, Appraisal $appraisal): void
    {
        abort_unless($this->canReview($user, $appraisal), 403, 'You are not the appraiser for this appraisal.');
    }

    protected function ensureCanCountersign(User $user, Appraisal $appraisal): void
    {
        $allowed = (Gate::allows('countersign appraisal') && $appraisal->isReviewerUser($user)) || Gate::allows('edit appraisal');
        abort_unless($allowed, 403, 'You are not the reviewer for this appraisal.');
    }

    /**
     * @param  array<int, AppraisalStatusEnum>  $allowed
     */
    protected function ensureStatus(Appraisal $appraisal, array $allowed): void
    {
        abort_unless(in_array($appraisal->status, $allowed, true), 422, 'This action is not allowed at the current stage.');
    }
}
