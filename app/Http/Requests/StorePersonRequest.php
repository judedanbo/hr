<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePersonRequest extends FormRequest
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
            'personalInformation.title' => 'string|min:1|max:10',
            'personalInformation.surname' => 'required|string|max:100',
            'personalInformation.first_name' => 'required|string|max:100',
            'personalInformation.other_names' => 'required|string|max:100',
            'personalInformation.date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
            ],
            'personalInformation.gender' => ['required', new Enum(Gender::class)],
            'personalInformation.marital_status' => ['required', new Enum(MaritalStatus::class)],
            'contactInformation.contact_type' => 'required|integer',
            'contactInformation.contact' => 'required|string|max:100|unique:contacts,contact',
            'employmentInformation.staff_number' => 'required|string|max:10|unique:institution_person,staff_number|different:employmentInformation.file_number',
            'employmentInformation.file_number' => 'required|string|max:10|unique:institution_person,file_number|different:employmentInformation.file_number',
            'employmentInformation.hire_date' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(5)->format('Y-m-d'),
                'before_or_equal:today',
            ],
        ];
    }
}
