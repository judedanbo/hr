<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppraisalRatingLevel>
 */
class AppraisalRatingLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = $this->faker->unique()->numberBetween(1, 5);

        return [
            'value' => $value,
            'label' => "Level {$value}",
            'min_score' => ($value - 1) * 20,
            'max_score' => $value * 20,
            'description' => $this->faker->sentence(),
            'color' => null,
        ];
    }
}
