<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Annual Leave', 'Sick Leave', 'Maternity Leave', 'Casual Leave',
            'Study Leave', 'Compassionate Leave', 'Paternity Leave',
        ]);

        return [
            'name' => $name,
            'code' => Str::upper(Str::slug($name, '_')) . '_' . fake()->unique()->numberBetween(1, 9999),
            'requires_evidence' => false,
            'gender_restriction' => null,
            'counts_weekends' => false,
            'counts_holidays' => false,
            'min_notice_days' => 0,
            'max_consecutive_days' => null,
            'max_concurrent_per_unit' => null,
            'color' => fake()->safeHexColor(),
            'is_active' => true,
        ];
    }

    public function calendarDays(): static
    {
        return $this->state(fn (array $attributes): array => [
            'counts_weekends' => true,
            'counts_holidays' => true,
        ]);
    }

    public function requiresEvidence(): static
    {
        return $this->state(fn (array $attributes): array => ['requires_evidence' => true]);
    }
}
