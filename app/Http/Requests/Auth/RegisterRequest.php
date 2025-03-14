<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'staff_number' => 'required|string|max:12|exists:institution_person,staff_number',
            'surname' => 'required|string|max:30',
            'first_name' => 'required|string|max:30',
            'email' => 'required|string|max:60|regex:/[A-Za-z]+\.[A-Za-z]+/i',
            // 'phone' => 'required|string|max:15|regex:/\d{10,15}/',
        ];
    }
}
