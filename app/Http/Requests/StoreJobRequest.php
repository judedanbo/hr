<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'institution_id' => 'required|integer|exists:institutions,id',
            'job_category_id' => 'integer|exists:job_categories,id|nullable',
            'start_date' => 'date|before_or_equal:today|after_or_equal:2000-01-01|nullable',
            'end_date' => 'date|after_or_equal:start_date|nullable',
        ];
    }
}
