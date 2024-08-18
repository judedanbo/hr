<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Qualification>
 */
class QualificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'person_id' => $this->faker->numberBetween(1, 100),
            'course' => $this->faker->word(),
            'institution' => $this->faker->company(),
            'qualification' => $this->faker->jobTitle(),
            'qualification_number' => Str::random(10),
            'level' => Str::random(20),
            'pk' => Str::random(6),
            'year' => $this->faker->numberBetween(1990, 2022),
        ];
    }
}
