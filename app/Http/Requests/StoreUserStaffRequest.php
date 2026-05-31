<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreUserStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('associate user staff');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'person_id' => [
                'required',
                'integer',
                Rule::exists('people', 'id')->where(fn ($q) => $q->whereNull('deleted_at')),
                Rule::exists('institution_person', 'person_id')->where(fn ($q) => $q->whereNull('deleted_at')),
                Rule::unique('users', 'person_id')->ignore($this->route('user')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.required' => 'Please select a staff record to associate.',
            'person_id.exists' => 'The selected record is not a valid staff member.',
            'person_id.unique' => 'That staff record is already linked to another user.',
        ];
    }
}
