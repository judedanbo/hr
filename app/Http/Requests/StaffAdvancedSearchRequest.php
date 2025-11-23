<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffAdvancedSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'rank_id' => ['nullable', 'integer', 'exists:jobs,id'],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'department_id' => ['nullable', 'integer', 'exists:units,id'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'status' => ['nullable', 'string', 'max:10'],
            'hire_date_from' => ['nullable', 'date'],
            'hire_date_to' => ['nullable', 'date', 'after_or_equal:hire_date_from'],
            'age_from' => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_to' => ['nullable', 'integer', 'min:18', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rank_id.exists' => 'The selected rank does not exist.',
            'job_category_id.exists' => 'The selected job category does not exist.',
            'unit_id.exists' => 'The selected unit does not exist.',
            'department_id.exists' => 'The selected department does not exist.',
            'gender.in' => 'Gender must be either Male or Female.',
            'hire_date_to.after_or_equal' => 'End date must be equal to or after the start date.',
        ];
    }
}
