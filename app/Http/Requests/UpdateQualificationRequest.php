<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQualificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
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
            "course" => "string|max:100",
            "institution" => "string|max:100",
            "qualification" => "string|max:100",
            "qualification_number" => "string|max:10",
            "level" => "string|max:50",
            "pk" => "string|max:6",
            "year" => "string|max:4"
        ];
    }
}