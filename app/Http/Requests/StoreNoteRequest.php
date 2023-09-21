<?php

namespace App\Http\Requests;

use App\Enums\NoteTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'note' => 'required|string',
            'note_type' => [
                new Enum(NoteTypeEnum::class),
                'max:3',
                'nullable'
            ],
            // 'notable_type' => 'required|string',
            // 'notable_id' => 'required|integer',
            'url' => 'string|nullable',
            // 'created_by' => 'required|integer|exists:users,id'

        ];
    }
}