<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveEntitlement>
 */
class LeaveEntitlementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_year_id' => LeaveYear::factory(),
            'leave_type_id' => LeaveType::factory(),
            'job_category_id' => null,
            'days_allowed' => fake()->numberBetween(5, 30),
            'min_service_months' => 0,
            'notes' => null,
        ];
    }
}
