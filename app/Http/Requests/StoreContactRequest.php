<?php

namespace App\Http\Requests;

use App\Enums\ContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'person_id' => 'required|integer|exists:people,id',
            'contact_type' => ['required', new Enum(ContactTypeEnum::class)],
            'contact' => 'required|string|min:5|max:255',
            'valid_end' => 'nullable|date',
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
            'person_id.required' => 'Please select a person.',
            'person_id.exists' => 'The selected person does not exist.',
            'contact_type.required' => 'Please select a contact type.',
            'contact.required' => 'Please enter the contact information.',
            'contact.min' => 'Contact must be at least 5 characters.',
            'contact.max' => 'Contact cannot exceed 255 characters.',
        ];
    }
}
