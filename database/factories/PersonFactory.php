<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dependent>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $gender = fake()->randomElements(['M', 'F']);
        return [
            'title' => fake()->title($gender),
            'surname' => fake()->lastName(),
            'other_names' => fake()->firstName($gender),
            'gender' => $gender[0],
            'nationality' => 'GH',
            // 'image' =>fake()-> ,
            'date_of_birth' => fake()->dateTimeBetween('-100 years', '-18 years'),
            // 'social_security_number' => fake()->phoneNumber(),
            // 'national_id_number' => fake()->phoneNumber(),
            'about' => fake()->paragraph(),
        ];
    }
}
