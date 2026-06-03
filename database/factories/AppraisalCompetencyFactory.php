<?php

namespace Database\Factories;

use App\Enums\CompetencyGroupEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppraisalCompetency>
 */
class AppraisalCompetencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucwords($this->faker->unique()->words(2, true)),
            'description' => $this->faker->sentence(),
            'group' => $this->faker->randomElement(CompetencyGroupEnum::cases()),
            'default_weight' => $this->faker->numberBetween(5, 25),
            'job_category_id' => null,
            'is_active' => true,
        ];
    }
}
