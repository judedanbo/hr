<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveDocument>
 */
class LeaveDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'title' => fake()->words(2, true),
            'file_name' => 'leave-documents/' . fake()->uuid() . '.pdf',
            'file_type' => 'application/pdf',
        ];
    }
}
