<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced (and logged) inside RoleController::addRole.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->route('user');

            if ($user instanceof User
                && is_null($user->person_id)
                && User::rolesIncludeStaff($this->input('roles'))
            ) {
                $validator->errors()->add(
                    'roles',
                    'Associate this user with a staff record before assigning the staff role.'
                );
            }
        });
    }
}
