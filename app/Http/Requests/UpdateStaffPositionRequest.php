<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateStaffPositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('update staff position');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'staff_id' => [
                'required',
                'exists:position_staff,staff_id',
            ],
            'position_id' => [
                'required',
                'exists:position_staff,position_id',
            ],
            'start_date' => 'date|nullable',
            'end_date' => 'date|after:start_date|nullable',
        ];
    }
}
