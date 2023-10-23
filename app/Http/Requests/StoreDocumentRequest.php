<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
            'document_type' => 'required|string|max:3',
            'document_title' => 'required|string|max:100',
            'document_number' => 'required|string|max:20',
            'document_file' => 'nullable|file|max:2048|mimes:pdf,png,jpg,jpeg',
            'document_url' => 'nullable|string|max:255',
            'document_status' => 'required|string|max:3',
            'document_remarks' => 'nullable|string|max:255',
            'documentable_type' => 'required|string|max:255',
            'documentable_id' => 'required|integer',
        ];
    }
}
