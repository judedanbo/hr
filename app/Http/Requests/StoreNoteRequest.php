<?php

namespace App\Http\Requests;

use App\Enums\NoteTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;

class StoreNoteRequest extends FormRequest
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
        return [
            'note' => 'required|string|max:1000',
            'note_type' => [
                'nullable',
                new Enum(NoteTypeEnum::class),
                'max:3',
            ],
            'note_date' => 'nullable|date',
            'url' => 'nullable|string|url|max:255',
            'notable_id' => 'required|integer|exists:institution_person,id',
            'notable_type' => 'nullable|string|max:255',
            'document' => 'nullable|array',
            'document.*.file' => [
                'required',
                'file',
                File::types(['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx']),
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'note.required' => 'Please enter a note.',
            'notable_id.required' => 'Staff member is required.',
            'notable_id.exists' => 'The selected staff member does not exist.',
            'document.*.file.required' => 'Please select a file to upload.',
            'document.*.file.max' => 'Each file must not exceed 10MB.',
            'document.*.file.mimes' => 'Only PDF, PNG, JPG, JPEG, DOC, and DOCX files are allowed.',
            'url.url' => 'Please enter a valid URL.',
        ];
    }
}
