<?php

namespace Database\Factories;

use App\Enums\UnitType;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitNames = [
            'IT Department', 'Human Resources', 'Finance', 'Administration',
            'Operations', 'Audit', 'Compliance', 'Legal', 'Procurement',
        ];

        return [
            'name' => fake()->randomElement($unitNames) . ' ' . fake()->numberBetween(1, 100),
            'type' => fake()->randomElement(UnitType::cases()),
            'unit_id' => null,
            'institution_id' => Institution::factory(),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'end_date' => null,
        ];
    }

    /**
     * Indicate that this is a department (parent unit).
     */
    public function department(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UnitType::DEPARTMENT,
            'unit_id' => null,
        ]);
    }

    /**
     * Indicate that this is a division.
     */
    public function division(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UnitType::DIVISION,
        ]);
    }

    /**
     * Indicate that this unit has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
