<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStaffStatusRequest extends FormRequest
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
            'status' => [
                "required",
                new Enum(EmployeeStatusEnum::class)
            ],
            'start_date' => 'required|date|before_or_equal:today|after_or_equal:institution_person.hire_date',
            'end_date' => 'date|after:start_date|nullable',
        ];
    }
}