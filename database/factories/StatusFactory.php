<?php

namespace Database\Factories;

use App\Enums\EmployeeStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => InstitutionPerson::factory(),
            'status' => EmployeeStatusEnum::Active,
            'description' => $this->faker->optional()->sentence(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'end_date' => null,
        ];
    }

    /**
     * Indicate that the staff is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatusEnum::Active,
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the staff is retired.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EmployeeStatusEnum::Retired,
            'end_date' => now(),
        ]);
    }

    /**
     * Indicate that the status has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
