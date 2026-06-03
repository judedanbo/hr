<?php

namespace App\Http\Requests;

use App\Enums\CompetencyGroupEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreAppraisalCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create appraisal competency');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:appraisal_competencies,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'group' => ['required', Rule::enum(CompetencyGroupEnum::class)],
            'default_weight' => ['required', 'integer', 'between:0,100'],
            'job_category_id' => ['nullable', 'exists:job_categories,id'],
            'is_active' => ['boolean'],
        ];
    }
}
