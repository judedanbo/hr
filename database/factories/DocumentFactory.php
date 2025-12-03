<?php

namespace Database\Factories;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement(DocumentTypeEnum::cases())->value,
            'document_title' => fake()->sentence(3),
            'document_number' => fake()->optional()->numerify('DOC-####'),
            'document_file' => null,
            'file_type' => null,
            'file_name' => null,
            'document_status' => fake()->randomElement(DocumentStatusEnum::cases())->value,
            'document_remarks' => fake()->optional()->sentence(),
            'documentable_type' => null,
            'documentable_id' => null,
        ];
    }
}
