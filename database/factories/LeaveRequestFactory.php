<?php

namespace Database\Factories;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');
        $end = (clone $start)->modify('+4 days');

        return [
            'staff_id' => InstitutionPerson::factory(),
            'leave_type_id' => LeaveType::factory(),
            'leave_year_id' => LeaveYear::factory(),
            'leave_plan_item_id' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'requested_days' => 5,
            'reason' => fake()->optional()->sentence(),
            'address_during_leave' => fake()->address(),
            'contact_during_leave' => fake()->phoneNumber(),
            'relieving_officer_id' => null,
            'status' => LeaveRequestStatusEnum::Pending,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => LeaveRequestStatusEnum::Cancelled]);
    }
}
