<?php

namespace App\Http\Requests;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['sometimes', 'required', new Enum(DocumentTypeEnum::class)],
            'document_title' => 'sometimes|required|string|max:100',
            'document_number' => 'nullable|string|max:20',
            'file_name' => 'nullable|file|max:10240|mimes:pdf,png,jpg,jpeg,doc,docx',
            'document_status' => ['sometimes', new Enum(DocumentStatusEnum::class)],
            'document_remarks' => 'nullable|string|max:255',
        ];
    }
}
