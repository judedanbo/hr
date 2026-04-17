<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreQualificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Users with the approval permission can create for anyone. Other users
     * (typically staff) may only create qualifications for themselves.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->can('approve staff qualification')) {
            return true;
        }

        return $user->person && (int) $this->input('person_id') === $user->person->id;
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
            'course' => 'required|string|max:100',
            'institution' => 'string|max:100|nullable',
            'qualification' => 'string|max:100|nullable',
            'qualification_number' => 'string|max:10|nullable',
            'level' => 'string|max:50|nullable',
            'pk' => 'string|max:6|nullable',
            'year' => 'string|max:4|nullable',

            // Per-file parallel arrays — all three are required together when any file is uploaded.
            'file_name' => 'nullable|array',
            'file_name.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_type' => 'nullable|array|required_with:file_name',
            'document_type.*' => ['required', new Enum(DocumentTypeEnum::class)],
            'document_title' => 'nullable|array|required_with:file_name',
            'document_title.*' => 'required|string|max:100',
        ];
    }
}
