<?php

namespace Tests\Feature\Appraisal;

use App\Models\Appraisal;
use App\Models\AppraisalCompetency;
use App\Models\AppraisalCycle;
use App\Services\Appraisal\AppraisalScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_computes_weighted_objective_competency_and_overall_scores(): void
    {
        $cycle = AppraisalCycle::factory()->create(['objectives_weight' => 70, 'competencies_weight' => 30]);
        $appraisal = Appraisal::factory()->create(['appraisal_cycle_id' => $cycle->id]);

        $appraisal->objectives()->create(['title' => 'A', 'weight' => 60, 'supervisor_score' => 80]);
        $appraisal->objectives()->create(['title' => 'B', 'weight' => 40, 'supervisor_score' => 90]);

        $competency = AppraisalCompetency::factory()->create();
        $appraisal->competencyRatings()->create(['appraisal_competency_id' => $competency->id, 'weight' => 100, 'supervisor_score' => 70]);

        $result = app(AppraisalScoringService::class)->score($appraisal);

        // objectives: (60*80 + 40*90)/100 = 84 ; competencies: 70
        $this->assertSame(84.0, $result['objectives_score']);
        $this->assertSame(70.0, $result['competencies_score']);
        // overall: (84*70 + 70*30)/100 = 79.8
        $this->assertSame(79.8, $result['overall_score']);
        $this->assertSame('Meets Expectations', $result['overall_band']);
    }

    public function test_apply_persists_scores_and_band(): void
    {
        $cycle = AppraisalCycle::factory()->create(['objectives_weight' => 100, 'competencies_weight' => 0]);
        $appraisal = Appraisal::factory()->create(['appraisal_cycle_id' => $cycle->id]);
        $appraisal->objectives()->create(['title' => 'A', 'weight' => 100, 'supervisor_score' => 92]);

        app(AppraisalScoringService::class)->apply($appraisal);

        $appraisal->refresh();
        $this->assertSame('92.00', (string) $appraisal->overall_score);
        $this->assertSame('Outstanding', $appraisal->overall_band);
    }
}
