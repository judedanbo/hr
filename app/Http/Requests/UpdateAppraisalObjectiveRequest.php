<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppraisalObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'weight' => ['required', 'integer', 'between:0,100'],
            'measure' => ['nullable', 'string', 'max:500'],
            'midyear_progress' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
