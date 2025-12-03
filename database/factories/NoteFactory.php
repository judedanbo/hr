<?php

namespace Database\Factories;

use App\Enums\NoteTypeEnum;
use App\Models\InstitutionPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note' => fake()->sentence(),
            'note_date' => fake()->optional()->date(),
            'note_type' => fake()->randomElement(array_filter(
                NoteTypeEnum::cases(),
                fn ($case) => $case !== NoteTypeEnum::NOT_PROVIDED
            ))?->value ?? NoteTypeEnum::RETIRED->value,
            'notable_type' => InstitutionPerson::class,
            'notable_id' => InstitutionPerson::factory(),
            'created_by' => 1,
            'url' => fake()->optional()->url(),
        ];
    }
}
