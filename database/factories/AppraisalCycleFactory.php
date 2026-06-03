<?php

namespace Database\Factories;

use App\Enums\AppraisalCycleStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppraisalCycle>
 */
class AppraisalCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = $this->faker->numberBetween(2024, 2027);

        return [
            'name' => "Appraisal Cycle {$year}",
            'year' => $year,
            'objective_window_start' => "{$year}-01-01",
            'objective_window_end' => "{$year}-01-31",
            'midyear_window_start' => "{$year}-06-01",
            'midyear_window_end' => "{$year}-06-30",
            'final_window_start' => "{$year}-12-01",
            'final_window_end' => "{$year}-12-31",
            'objectives_weight' => 70,
            'competencies_weight' => 30,
            'status' => AppraisalCycleStatusEnum::Draft,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => AppraisalCycleStatusEnum::Open]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => AppraisalCycleStatusEnum::Closed]);
    }
}
