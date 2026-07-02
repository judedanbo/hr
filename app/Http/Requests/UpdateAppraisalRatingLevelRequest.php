<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateAppraisalRatingLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('edit appraisal rating level');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'between:1,10', Rule::unique('appraisal_rating_levels', 'value')->ignore($this->route('appraisalRatingLevel'))],
            'label' => ['required', 'string', 'max:255'],
            'min_score' => ['required', 'numeric', 'between:0,100'],
            'max_score' => ['required', 'numeric', 'between:0,100', 'gte:min_score'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
        ];
    }
}
