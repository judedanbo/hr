<?php

namespace Database\Factories;

use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_year_id' => LeaveYear::factory(),
            'date' => fake()->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'name' => fake()->randomElement([
                'New Year', 'Independence Day', 'Labour Day', 'Founders Day',
                'Christmas Day', 'Boxing Day', 'Republic Day',
            ]),
            'is_recurring' => false,
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes): array => ['is_recurring' => true]);
    }
}
