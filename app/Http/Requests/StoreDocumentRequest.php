<?php

namespace App\Http\Requests;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDocumentRequest extends FormRequest
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
            'document_type' => ['required', new Enum(DocumentTypeEnum::class)],
            'document_title' => 'required|string|max:100',
            'document_number' => 'nullable|string|max:20',
            'document_file' => 'required|file|max:10240|mimes:pdf,png,jpg,jpeg,doc,docx',
            'document_url' => 'nullable|string|max:255',
            'document_status' => ['required', new Enum(DocumentStatusEnum::class)],
            'document_remarks' => 'nullable|string|max:255',
            'documentable_type' => 'nullable|string|max:255',
            'documentable_id' => 'nullable|integer',
        ];
    }
}
