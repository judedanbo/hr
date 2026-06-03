<?php

namespace Database\Factories;

use App\Enums\AppraisalStatusEnum;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appraisal>
 */
class AppraisalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appraisal_cycle_id' => AppraisalCycle::factory(),
            'staff_id' => InstitutionPerson::factory(),
            'appraiser_id' => null,
            'reviewer_id' => null,
            'unit_id' => null,
            'status' => AppraisalStatusEnum::DraftObjectives,
        ];
    }
}
