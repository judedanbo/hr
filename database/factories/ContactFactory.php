<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $type = fake()->randomElements([1, 2, 3]);
        $contact = '';
        if ($type == 1) {
            $contact = fake()->phoneNumber();
        }
        if ($type == 2) {
            $contact = fake()->safeEmail();
        }
        if ($type == 3) {
            $contact = fake()->address();
        }

        return [
            'person_id' => Person::factory(),
            'type' => $type,
            'contact' => $contact,
        ];
    }
}
