<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstitutionRequest extends FormRequest
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
            'id' => 'required|integer|exists:institutions',
            'name' => 'required|string|max:100',
            'abbreviation' => 'required|string|max:6',
            'status' => 'string|max:10|nullable',
            'start_date' => 'required|date|before_or_equal:today|after_or_equal:2000-01-01',
            'end_date' => 'date|after_or_equal:today|after:start_date|nullable',
        ];
    }
}