<?php

namespace Database\Factories;

use App\Models\LeavePlan;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeavePlanItem>
 */
class LeavePlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+3 months');
        $end = (clone $start)->modify('+4 days');

        return [
            'leave_plan_id' => LeavePlan::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'proposed_days' => fake()->numberBetween(1, 5),
            'note' => null,
            'converted_request_id' => null,
        ];
    }
}
