<?php

namespace Database\Factories;

use App\Models\LeaveYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeavePlanningWindow>
 */
class LeavePlanningWindowFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_year_id' => LeaveYear::factory(),
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
            'instructions' => null,
            'unit_id' => null,
            'allow_after_close' => false,
            'require_full_plan' => false,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addWeek(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'opens_at' => now()->subWeeks(2),
            'closes_at' => now()->subDay(),
            'allow_after_close' => false,
        ]);
    }
}
