<?php

namespace App\Http\Requests;

use App\Enums\Identity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdentityRequest extends FormRequest
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
        $person = $this->route('person');

        return
            [
                'id_type' => ['required', Rule::enum(Identity::class)],
                'id_number' => [
                    'required', 'min:5', 'max:30',
                    Rule::unique('person_identities', 'id_number')
                        ->where('id_type', 'G'),
                    Rule::unique('person_identities', 'id_number')
                        ->where('person_id', $person->id),
                    Rule::prohibitedIf(function () use ($person) {
                        return $person->identities->where('id_type', Identity::GhanaCard)->count() > 0 && $this->id_type == 'G';
                    }),
                ],
            ];
    }

    public function messages()
    {
        return [
            'id_type.required' => 'The ID type is required',
            'id_number.required' => 'The ID number is required',
            'id_number.min' => 'The ID number must be at least 5 characters',
            'id_number.max' => 'The ID number must not be greater than 30 characters',
            'id_number.unique' => 'The ID number has already been taken',
            'id_number.prohibited' => 'The person already has a Ghana Card',
        ];
    }
}
