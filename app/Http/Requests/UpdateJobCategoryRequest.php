<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => 'required|integer|exists:job_categories,id',
            'name' => 'required|string|max:100',
            'short_name' => 'string|max:10|unique:job_categories,id|nullable',
            'level' => [
                'required',
                Rule::unique('job_categories', 'level')->ignore($this->id),
                'integer:between:1,100',
            ],
            'job_category_id' => 'nullable|integer|exists:job_categories,id',
            'description' => 'nullable|string',
            'institution_id' => 'required|integer|exists:institutions,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ];
    }
}
