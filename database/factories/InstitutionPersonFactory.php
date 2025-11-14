<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\JobCategory;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstitutionPerson>
 */
class InstitutionPersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'person_id' => Person::factory(),
            'file_number' => fake()->unique()->numerify('FILE########'),
            'staff_number' => fake()->unique()->numerify('STAFF#######'),
            'old_staff_number' => fake()->optional()->numerify('OLD#######'),
            'hire_date' => fake()->dateTimeBetween('-35years', '-1 month'),
            'end_date' => null,
        ];
    }

    /**
     * Indicate that the staff member is retired/separated.
     *
     * @return static
     */
    public function separated()
    {
        return $this->state(function (array $attributes) {
            return [
                'end_date' => fake()->dateTimeBetween('-2 years', 'now'),
            ];
        });
    }

    /**
     * Indicate that the staff member has a specific staff number.
     *
     * @return static
     */
    public function withStaffNumber(string $staffNumber)
    {
        return $this->state(function (array $attributes) use ($staffNumber) {
            return [
                'staff_number' => $staffNumber,
            ];
        });
    }

    /**
     * Indicate that the staff member has a specific file number.
     *
     * @return static
     */
    public function withFileNumber(string $fileNumber)
    {
        return $this->state(function (array $attributes) use ($fileNumber) {
            return [
                'file_number' => $fileNumber,
            ];
        });
    }

    /**
     * Indicate that the staff member was hired recently.
     *
     * @return static
     */
    public function recentHire()
    {
        return $this->state(function (array $attributes) {
            return [
                'hire_date' => fake()->dateTimeBetween('-3 months', 'now'),
            ];
        });
    }

    /**
     * Indicate that the staff member is a long-term employee.
     *
     * @return static
     */
    public function longTerm()
    {
        return $this->state(function (array $attributes) {
            return [
                'hire_date' => fake()->dateTimeBetween('-30 years', '-10 years'),
            ];
        });
    }

    /**
     * Indicate that the staff member has no old staff number.
     *
     * @return static
     */
    public function withoutOldStaffNumber()
    {
        return $this->state(function (array $attributes) {
            return [
                'old_staff_number' => null,
            ];
        });
    }
}
