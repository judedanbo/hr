<?php

namespace Database\Factories;

use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\Person;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QualificationFactory extends Factory
{
    protected $model = Qualification::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'course' => $this->faker->word(),
            'institution' => $this->faker->company(),
            'qualification' => $this->faker->jobTitle(),
            'qualification_number' => Str::random(10),
            'level' => $this->faker->randomElement(QualificationLevelEnum::cases())->value,
            'pk' => Str::random(6),
            'year' => (string) $this->faker->numberBetween(1990, 2025),
            'status' => QualificationStatusEnum::Approved->value,
            'approved_by' => null,
            'approved_at' => now(),
        ];
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Pending->value,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Rejected->value,
        ]);
    }

    public function atLevel(QualificationLevelEnum $level): self
    {
        return $this->state(fn () => ['level' => $level->value]);
    }
}
