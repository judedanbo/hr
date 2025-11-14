<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobCategory>
 */
class JobCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Administrative', 'Technical', 'Professional', 'Managerial',
            'Clerical', 'Security', 'Maintenance', 'Support'
        ];
        
        $name = fake()->randomElement($categories) . ' ' . fake()->word();
        
        return [
            'name' => $name,
            'short_name' => strtoupper(substr($name, 0, 5)),
            'level' => fake()->numberBetween(1, 10),
            'job_category_id' => null, // Parent category - can be set explicitly
            'description' => fake()->sentence(),
            'institution_id' => Institution::factory(),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'end_date' => null,
        ];
    }

    /**
     * Indicate that the job category has ended.
     *
     * @return static
     */
    public function ended()
    {
        return $this->state(function (array $attributes) {
            return [
                'end_date' => fake()->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }

    /**
     * Indicate that this is a sub-category.
     *
     * @return static
     */
    public function subCategory()
    {
        return $this->state(function (array $attributes) {
            return [
                'job_category_id' => \App\Models\JobCategory::factory(),
            ];
        });
    }
}