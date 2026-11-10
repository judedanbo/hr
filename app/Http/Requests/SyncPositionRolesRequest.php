<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPositionRolesRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `can:manage position roles` route
     * middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Roles are addressed by name, matching the `roles.list` endpoint and
     * UpdateUserRolesRequest. `present` rather than `required` so clearing the
     * mapping is expressible.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['present', 'array'],
            'roles.*' => [
                'string',
                'distinct',
                'exists:roles,name',
                Rule::notIn($this->blockedRoles()),
            ],
        ];
    }

    /**
     * `staff` carries the users.person_id invariant enforced at three separate
     * call sites, which a position grant would bypass — and being staff is a
     * property of the employment record, not of holding a post.
     * `super-administrator` would let anyone holding `create position` plus
     * `manage position roles` mint themselves a super admin.
     *
     * @return array<int, string>
     */
    protected function blockedRoles(): array
    {
        return ['staff', 'super-administrator'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.*.not_in' => 'The :input role cannot be granted through a position.',
        ];
    }
}
