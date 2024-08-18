<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\Nationality;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateInstitutionPersonRequest extends FormRequest
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
            'staffData.personalInformation.id' => 'required',
            'staffData.personalInformation.title' => 'string|min:1|max:10|nullable',
            'staffData.personalInformation.first_name' => 'required|string|max:100',
            'staffData.personalInformation.surname' => 'required|string|max:100',
            'staffData.personalInformation.other_names' => 'string|max:100|nullable',
            'staffData.personalInformation.date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
            ],
            'staffData.personalInformation.place_of_birth' => 'string|max:100|nullable',
            'staffData.personalInformation.country_of_birth' => ['nullable'],
            'staffData.personalInformation.gender' => ['required', new Enum(GenderEnum::class)],
            'staffData.personalInformation.marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'staffData.personalInformation.religion' => 'string|max:40|nullable',
            'staffData.personalInformation.nationality' => [new Enum(Nationality::class), 'nullable'],
            'staffData.personalInformation.ethnicity' => 'string|max:40|nullable',
            'staffData.personalInformation.about' => 'string|max:255|nullable',
            // "staffData.image.image" => 'nullable',
            'staffData.employmentInformation.hire_date' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(50)->format('Y-m-d'),
                'before_or_equal:today',
            ],
            'staffData.employmentInformation.file_number' => 'required|max:12',
            'staffData.employmentInformation.staff_number' => 'required|max:12',
        ];
    }

    public function attributes(): array
    {
        return [
            // "staffData.personalInformation.id" => "required",
            'staffData.personalInformation.title' => 'Staff title',
            'staffData.personalInformation.first_name' => 'staff first name',
            'staffData.personalInformation.surname' => 'Staff surname',
            'staffData.personalInformation.other_name' => 'staff other name',
            'staffData.personalInformation.date_of_birth' => 'Date of birth',
            'staffData.personalInformation.place_of_birth' => 'place of birth',
            'staffData.personalInformation.country_of_birth' => 'Country of Birth',
            'staffData.personalInformation.gender' => 'Gender',
            'staffData.personalInformation.marital_status' => 'Marital Status',
            'staffData.personalInformation.religion' => 'Religion',
            'staffData.personalInformation.nationality' => 'Nationality',
            'staffData.personalInformation.ethnicity' => 'Ethnicity',
            'staffData.personalInformation.about' => 'staff short description',
            'staffData.image.image' => 'staff image',
            'staffData.employmentInformation.hire_date' => 'Date hired',
            'staffData.employmentInformation.file_number' => 'staff file number',
            'staffData.employmentInformation.staff_number' => 'staff number',
        ];
    }
}
