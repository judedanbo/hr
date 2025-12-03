<?php

namespace App\Http\Requests;

use App\Enums\NoteTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateNoteRequest extends FormRequest
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
            'note' => 'required|string|max:1000',
            'note_type' => [
                'nullable',
                new Enum(NoteTypeEnum::class),
            ],
            'note_date' => 'nullable|date',
            'url' => 'nullable|string|url|max:255',
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
            'note.required' => 'Please enter a note.',
            'note.max' => 'Note cannot exceed 1000 characters.',
            'url.url' => 'Please enter a valid URL.',
        ];
    }
}
