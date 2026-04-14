<?php

namespace App\Http\Requests;

use App\Enums\OfficeTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOfficeForUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit unit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'type' => ['required', 'string', Rule::enum(OfficeTypeEnum::class)],
            'district_id' => 'required|integer|exists:districts,id',
            'start_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter an office name.',
            'name.max' => 'Office name cannot exceed 150 characters.',
            'type.required' => 'Please select an office type.',
            'district_id.required' => 'Please select a district.',
            'district_id.exists' => 'The selected district does not exist.',
        ];
    }
}
