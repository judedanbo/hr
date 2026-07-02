<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppraisalObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership / role is enforced in the controller against the parent appraisal.
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
        ];
    }
}
