<?php

namespace App\Services\Appraisal;

use App\Models\Appraisal;
use App\Models\AppraisalRatingLevel;

class AppraisalScoringService
{
    /**
     * Compute the weighted objective, competency and overall scores for an
     * appraisal using supervisor scores (0-100 per item), weighted by item
     * weight and combined per the cycle's objectives/competencies split.
     *
     * @return array{objectives_score: float|null, competencies_score: float|null, overall_score: float|null, overall_band: string|null}
     */
    public function score(Appraisal $appraisal): array
    {
        $appraisal->loadMissing(['cycle', 'objectives', 'competencyRatings']);

        $objectivesScore = $this->weightedAverage(
            $appraisal->objectives->map(fn ($objective) => [
                'weight' => (int) $objective->weight,
                'score' => $objective->supervisor_score,
            ])->all(),
        );

        $competenciesScore = $this->weightedAverage(
            $appraisal->competencyRatings->map(fn ($rating) => [
                'weight' => (int) $rating->weight,
                'score' => $rating->supervisor_score,
            ])->all(),
        );

        $objectivesWeight = (int) ($appraisal->cycle?->objectives_weight ?? 0);
        $competenciesWeight = (int) ($appraisal->cycle?->competencies_weight ?? 0);

        $overall = null;
        if ($objectivesScore !== null || $competenciesScore !== null) {
            $overall = round(
                (($objectivesScore ?? 0) * $objectivesWeight + ($competenciesScore ?? 0) * $competenciesWeight) / 100,
                2,
            );
        }

        return [
            'objectives_score' => $objectivesScore,
            'competencies_score' => $competenciesScore,
            'overall_score' => $overall,
            'overall_band' => $overall !== null ? AppraisalRatingLevel::bandFor($overall)?->label : null,
        ];
    }

    /**
     * Persist the computed scores onto the appraisal.
     */
    public function apply(Appraisal $appraisal): Appraisal
    {
        $appraisal->update($this->score($appraisal));

        return $appraisal->refresh();
    }

    /**
     * Weighted average of {weight, score} rows, ignoring rows with no score.
     *
     * @param  array<int, array{weight: int, score: mixed}>  $rows
     */
    protected function weightedAverage(array $rows): ?float
    {
        $totalWeight = 0;
        $weightedSum = 0.0;

        foreach ($rows as $row) {
            if ($row['score'] === null) {
                continue;
            }

            $totalWeight += $row['weight'];
            $weightedSum += $row['weight'] * (float) $row['score'];
        }

        if ($totalWeight === 0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }
}
