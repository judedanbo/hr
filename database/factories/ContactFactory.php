<?php

namespace Database\Factories;

use App\Enums\ContactTypeEnum;
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
    public function definition(): array
    {
        $type = fake()->randomElement(ContactTypeEnum::cases());
        $contact = match ($type) {
            ContactTypeEnum::EMAIL => fake()->safeEmail(),
            ContactTypeEnum::PHONE => fake()->numerify('024#######'),
            ContactTypeEnum::ADDRESS => fake()->address(),
            ContactTypeEnum::GHPOSTGPS => 'GA-' . fake()->numerify('###-####'),
            ContactTypeEnum::EMERGENCY => fake()->numerify('024#######'),
        };

        return [
            'person_id' => Person::factory(),
            'contact_type' => $type->value,
            'contact' => $contact,
            'valid_end' => null,
        ];
    }
}
