<?php

namespace App\Http\Requests;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateQualificationDocumentRequest extends FormRequest
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
            'document_type' => ['required', 'string', new Enum(DocumentTypeEnum::class)],
            'document_title' => 'required|string|max:100',
            'document_number' => 'string|max:20|nullable',
            'document_file' => 'string|max:100|nullable',
            'file_name' => 'required|file|max:2048|mimes:pdf,png,jpg,jpeg',
            'file_type' => 'required|string|max:100',
            'document_status' => [ 'string', new Enum(DocumentStatusEnum::class)],
            'document_remarks' => 'string|max:255|nullable',
        ];
    }
}
