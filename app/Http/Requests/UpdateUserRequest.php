<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->hasRole('super-administrator');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                Rule::exists('users', 'id'),
                Rule::unique('users', 'id')->ignore($this->route('user')),
            ],
            'name' => 'required|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
        ];
    }
}
