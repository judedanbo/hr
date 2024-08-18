<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
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
            'staffData.personalInformation.title' => 'string|min:1|max:10|nullable',
            'staffData.personalInformation.surname' => 'required|string|max:100',
            'staffData.personalInformation.first_name' => 'required|string|max:100',
            'staffData.personalInformation.other_names' => 'string|max:100|nullable',
            'staffData.personalInformation.date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
            ],
            'staffData.personalInformation.gender' => ['required', new Enum(GenderEnum::class)],
            'staffData.personalInformation.marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'staffData.contactInformation.contact_type' => 'required|integer',
            'staffData.contactInformation.contact' => 'required|string|max:100|unique:contacts,contact',
            'staffData.employmentInformation.staff_number' => 'required|string|max:10|unique:institution_person,staff_number|different:staffData.employmentInformation.file_number',
            'staffData.employmentInformation.file_number' => 'required|string|max:10|unique:institution_person,file_number|different:staffData.employmentInformation.staff_number',
            'staffData.employmentInformation.hire_date' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(5)->format('Y-m-d'),
                'before_or_equal:today',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'staffData.personalInformation.title' => 'Title',
            'staffData.personalInformation.surname' => 'Surname',
            'staffData.personalInformation.first_name' => 'First name',
            'staffData.personalInformation.other_names' => 'Other names',
            'staffData.personalInformation.date_of_birth' => 'Date of birth',
            'staffData.personalInformation.gender' => 'gender',
            'staffData.personalInformation.marital_status' => 'Marital status',
            'staffData.contactInformation.contact_type' => 'Contact type',
            'staffData.contactInformation.contact' => 'Contact',
            'staffData.employmentInformation.file_number' => 'File_number',
            'staffData.employmentInformation.hire_date' => 'Hire_date',
        ];
    }

    public function messages(): array
    {
        return [
            'staffData.personalInformation.date_of_birth.before' => 'The staff must be over 18 years',
            'staffData.personalInformation.date_of_birth.before_or_equal' => 'The date of birth cannot after today',
        ];
    }
}
