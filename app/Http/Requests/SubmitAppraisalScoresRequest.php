<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAppraisalScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role / ownership is enforced in the controller against the appraisal.
        return $this->user() !== null;
    }

    /**
     * Shared shape for self-appraisal and supervisor-review score submissions.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'objectives' => ['array'],
            'objectives.*.id' => ['required', 'integer'],
            'objectives.*.score' => ['nullable', 'numeric', 'between:0,100'],
            'objectives.*.comment' => ['nullable', 'string', 'max:2000'],
            'competencies' => ['array'],
            'competencies.*.id' => ['required', 'integer'],
            'competencies.*.score' => ['nullable', 'numeric', 'between:0,100'],
            'competencies.*.comment' => ['nullable', 'string', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
