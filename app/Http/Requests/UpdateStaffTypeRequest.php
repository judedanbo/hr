<?php

namespace App\Http\Requests;

use App\Enums\StaffTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStaffTypeRequest extends FormRequest
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
            'staff_type' => [
                'required',
                new Enum(StaffTypeEnum::class)
            ],
            'start_date' => 'required|date|before_or_equal:today|after_or_equal:hire_date', // TODO: add hire_date to institution_person
            'end_date' => 'date|after:start_date|nullable',
        ];
    }
}
