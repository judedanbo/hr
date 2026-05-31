<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'userData.bio.name' => 'required|max:255',
            'userData.bio.email' => 'required|email|unique:users,email',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roles = (array) $this->input('userData.roles', []);

            if (in_array('staff', $roles, true)) {
                $validator->errors()->add(
                    'userData.roles',
                    'A new user cannot be given the staff role. Create the user first, then associate them with a staff record.'
                );
            }
        });
    }
}
