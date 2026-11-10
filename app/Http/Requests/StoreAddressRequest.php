<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The address is added to the person in the route, so it needs the update
     * ability on that person.
     */
    public function authorize(): bool
    {
        $person = $this->route('person');

        return $person instanceof Person && $this->user()->can('update', $person);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'post_code' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_line_1.required' => 'Address line 1 is required.',
            'address_line_1.max' => 'Address line 1 cannot exceed 255 characters.',
            'city.required' => 'City is required.',
            'city.max' => 'City cannot exceed 100 characters.',
            'country.required' => 'Country is required.',
            'country.max' => 'Country cannot exceed 100 characters.',
        ];
    }
}
