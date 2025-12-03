<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\JobCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ranks = [
            'Senior Officer', 'Junior Officer', 'Principal Officer',
            'Chief Officer', 'Assistant Director', 'Deputy Director',
            'Director', 'Manager', 'Supervisor', 'Clerk',
        ];

        return [
            'name' => fake()->randomElement($ranks) . ' ' . fake()->numberBetween(1, 5),
            'institution_id' => Institution::factory(),
            'job_category_id' => JobCategory::factory(),
        ];
    }
}
