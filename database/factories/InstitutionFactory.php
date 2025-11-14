<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'abbreviation' => fake()->lexify(strtoupper(substr($name, 0, 3)) . '??'),
            'start_date' => fake()->dateTimeBetween('-20 years', '-1 year'),
            'end_date' => null,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the institution is inactive.
     *
     * @return static
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
                'end_date' => fake()->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }
}