<?php

namespace Database\Factories;

use App\Models\InstitutionPerson;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveBalanceAdjustment>
 */
class LeaveBalanceAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => InstitutionPerson::factory(),
            'leave_type_id' => LeaveType::factory(),
            'leave_year_id' => LeaveYear::factory(),
            'days' => fake()->numberBetween(-5, 5) ?: 3,
            'reason' => fake()->sentence(),
            'adjusted_by' => null,
        ];
    }
}
