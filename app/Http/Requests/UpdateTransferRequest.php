<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransferRequest extends FormRequest
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
            'staff_id' => 'required|exists:institution_person,id',
            'unit_id' => 'required|exists:units,id',
            'start_date' => 'required|date|',
            'end_date' => 'date|after:start_date|nullable',
            'remarks' => 'string|max:100|nullable',
        ];
    }
}