<?php

namespace App\Http\Requests;

use App\Models\User;
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
            // A brand-new user can never be linked to a staff record yet, so the
            // staff role must not be assignable at creation. rolesIncludeStaff
            // flattens the payload, so it catches both the flat (userData.roles)
            // and the real multi-step (userData.roles.roles) shapes.
            if (User::rolesIncludeStaff($this->input('userData.roles'))) {
                $validator->errors()->add(
                    'userData.roles',
                    'A new user cannot be given the staff role. Create the user first, then associate them with a staff record.'
                );
            }
        });
    }
}
