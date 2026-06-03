<?php

namespace Database\Factories;

use App\Models\InstitutionPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalDelegation>
 */
class ApprovalDelegationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegator_id' => InstitutionPerson::factory(),
            'delegate_id' => InstitutionPerson::factory(),
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'reason' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->subWeek()->toDateString(),
        ]);
    }
}
