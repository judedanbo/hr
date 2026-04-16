<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffDirectoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'rank_id' => ['nullable', 'integer', 'exists:jobs,id'],
            'sub_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'hire_date_from' => ['nullable', 'date'],
            'hire_date_to' => ['nullable', 'date', 'after_or_equal:hire_date_from'],
            'age_from' => ['nullable', 'integer', 'min:16', 'max:100'],
            'age_to' => ['nullable', 'integer', 'min:16', 'max:100', 'gte:age_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
