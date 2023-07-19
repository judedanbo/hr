<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualificationRequest extends FormRequest
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
            "person_id" => "required|integer|exists:people,id",
            "course" => "required|string|max:100",
            "institution" => "string|max:100|nullable",
            "qualification" => "string|max:100|nullable",
            "qualification_number" => "string|max:10|nullable",
            "level" => "string|max:50|nullable",
            "pk" => "string|max:6|nullable",
            "year" => "string|max:4|nullable"
        ];
    }
}