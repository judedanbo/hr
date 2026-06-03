<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Services\Appraisal\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AppraisalController extends Controller
{
    public function __construct(protected AppraisalService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Appraisal/Index', [
            'appraisals' => Appraisal::query()
                ->with(['cycle:id,name,year', 'staff.person', 'appraiser.person', 'unit:id,name'])
                ->when($request->cycle_id, fn ($query, $cycleId) => $query->where('appraisal_cycle_id', $cycleId))
                ->latest()
                ->paginate(per_page())
                ->withQueryString()
                ->through(fn ($appraisal) => $this->summary($appraisal)),
            'cycles' => AppraisalCycle::query()->orderByDesc('year')->get(['id as value', 'name as label']),
            'filters' => $request->all('cycle_id'),
        ]);
    }

    public function show(Appraisal $appraisal)
    {
        $user = request()->user();

        $canViewAll = Gate::allows('view all appraisals');
        abort_unless(
            $canViewAll || $appraisal->isOwnedBy($user) || $appraisal->isAppraiserUser($user) || $appraisal->isReviewerUser($user),
            403,
            'You do not have permission to view this appraisal.',
        );

        $appraisal->load([
            'cycle',
            'staff.person',
            'appraiser.person',
            'reviewer.person',
            'unit:id,name',
            'objectives',
            'competencyRatings.competency:id,name,group',
            'statusHistories.actor:id,name',
        ]);

        return Inertia::render('Appraisal/Show', [
            'appraisal' => $this->detail($appraisal, $user),
        ]);
    }

    public function initiate(AppraisalCycle $appraisalCycle)
    {
        $count = $this->service->initiateCycle($appraisalCycle);

        return redirect()->back()->with('success', "Initiated {$count} appraisal(s) for {$appraisalCycle->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(Appraisal $appraisal): array
    {
        return [
            'id' => $appraisal->id,
            'cycle' => $appraisal->cycle?->name,
            'year' => $appraisal->cycle?->year,
            'staff_name' => $appraisal->staff?->person?->full_name,
            'appraiser_name' => $appraisal->appraiser?->person?->full_name ?? 'Unassigned',
            'unit' => $appraisal->unit?->name,
            'status' => $appraisal->status->value,
            'status_label' => $appraisal->status->label(),
            'status_color' => $appraisal->status->color(),
            'overall_score' => $appraisal->overall_score !== null ? (float) $appraisal->overall_score : null,
            'overall_band' => $appraisal->overall_band,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function detail(Appraisal $appraisal, $user): array
    {
        return array_merge($this->summary($appraisal), [
            'reviewer_name' => $appraisal->reviewer?->person?->full_name ?? 'Unassigned',
            'objectives_weight' => $appraisal->cycle?->objectives_weight,
            'competencies_weight' => $appraisal->cycle?->competencies_weight,
            'objectives_score' => $appraisal->objectives_score !== null ? (float) $appraisal->objectives_score : null,
            'competencies_score' => $appraisal->competencies_score !== null ? (float) $appraisal->competencies_score : null,
            'self_submitted_at' => $appraisal->self_submitted_at?->format('Y-m-d H:i'),
            'acknowledged_at' => $appraisal->acknowledged_at?->format('Y-m-d H:i'),
            'objectives' => $appraisal->objectives->map(fn ($objective) => [
                'id' => $objective->id,
                'title' => $objective->title,
                'description' => $objective->description,
                'weight' => $objective->weight,
                'measure' => $objective->measure,
                'midyear_progress' => $objective->midyear_progress,
                'self_score' => $objective->self_score !== null ? (float) $objective->self_score : null,
                'supervisor_score' => $objective->supervisor_score !== null ? (float) $objective->supervisor_score : null,
                'comment' => $objective->comment,
            ]),
            'competency_ratings' => $appraisal->competencyRatings->map(fn ($rating) => [
                'id' => $rating->id,
                'competency' => $rating->competency?->name,
                'weight' => $rating->weight,
                'self_score' => $rating->self_score !== null ? (float) $rating->self_score : null,
                'supervisor_score' => $rating->supervisor_score !== null ? (float) $rating->supervisor_score : null,
                'comment' => $rating->comment,
            ]),
            'history' => $appraisal->statusHistories->map(fn ($history) => [
                'status_label' => $history->status->label(),
                'actor' => $history->actor?->name,
                'comment' => $history->comment,
                'at' => $history->created_at?->format('Y-m-d H:i'),
            ]),
            'objectives_total_weight' => (int) $appraisal->objectives->sum('weight'),
            'can' => [
                'view_all' => Gate::allows('view all appraisals'),
                'is_owner' => $appraisal->isOwnedBy($user),
                'is_appraiser' => $appraisal->isAppraiserUser($user),
                'is_reviewer' => $appraisal->isReviewerUser($user),
                'review' => Gate::allows('review appraisal') && $appraisal->isAppraiserUser($user),
                'countersign' => Gate::allows('countersign appraisal') && $appraisal->isReviewerUser($user),
            ],
        ]);
    }
}
