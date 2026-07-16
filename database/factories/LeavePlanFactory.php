<?php

namespace Database\Factories;

use App\Enums\LeavePlanStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeavePlan>
 */
class LeavePlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => InstitutionPerson::factory(),
            'leave_year_id' => LeaveYear::factory(),
            'status' => LeavePlanStatusEnum::Draft,
            'submitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LeavePlanStatusEnum::Submitted,
            'submitted_at' => now(),
        ]);
    }
}
