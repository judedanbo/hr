<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionPersonRequest extends FormRequest
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
            'staffData.bio.title' => 'string|min:1|max:10|nullable',
            'staffData.bio.surname' => 'required|string|max:100',
            'staffData.bio.first_name' => 'required|string|max:100',
            'staffData.bio.other_names' => 'string|max:100|nullable',
            'staffData.bio.date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(100)->format('Y-m-d'),
            ],
            'staffData.bio.gender' => ['required', new Enum(GenderEnum::class)],
            'staffData.bio.marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'staffData.addressContact.address_line_1' => 'required|string|max:150',
            'staffData.addressContact.address_line_2' => 'string|max:150|nullable',
            'staffData.addressContact.post_code' => 'string|min:2|max:20|nullable',
            'staffData.addressContact.city' => 'required|string|max:35',
            'staffData.addressContact.region' => 'required|string|max:35|unique:contacts,contact',
            'staffData.addressContact.country' => 'required|string|max:100|unique:contacts,contact',
            'staffData.addressContact.contact_type' => 'required|integer|unique:contacts,contact',
            'staffData.addressContact.contact' => 'required|string|max:100|unique:contacts,contact',
            'staffData.employment.staff_number' => 'required|string|max:10|unique:institution_person,staff_number|different:staffData.employment.file_number',
            'staffData.employment.file_number' => 'required|string|max:10|unique:institution_person,file_number|different:staffData.employment.staff_number',
            'staffData.employment.hire_date' => [
                'required',
                'date',
                'after:' . Carbon::now()->subYears(5)->format('Y-m-d'),
                'before_or_equal:today',
            ],
            'staffData.qualifications.institution' =>"required|string|max:50",
            'staffData.qualifications.course' => "required|string|max:100",
            'staffData.qualifications.level' => "string|max:100|nullable",
            'staffData.qualifications.qualification' => "string|max:100|nullable",
            'staffData.qualifications.qualification_number' => "string|max:20|nullable",
            'staffData.qualifications.year' => "required|string|max:4",
            'staffData.rank.rank_id' => "required|exists:jobs,id",
            'staffData.rank.start_date' => "required|date|after:" . Carbon::now()->subYears(2)->format('Y-m-d') . "|before:" . Carbon::now()->addYear()->format('Y-m-d'),
            'staffData.rank.end_date' => "date|after:staffData.rank.start_date|before:" . Carbon::now()->subYear()->format('Y-m-d'),
            'staffData.unit.unit_id' => "required|exists:units,id",
            'staffData.unit.start_date' => "required|date|after:" . Carbon::now()->subYears(2)->format('Y-m-d') . "|before:" . Carbon::now()->addYear()->format('Y-m-d'),
            'staffData.unit.end_date' => "date|after:staffData.unit.start_date|before:" . Carbon::now()->addYear()->format('Y-m-d'),
        ];
    }

    public function attributes(): array
    {
        return [
            'staffData.bio.title' => 'Title',
            'staffData.bio.surname' => 'Surname',
            'staffData.bio.first_name' => 'First name',
            'staffData.bio.other_names' => 'Other names',
            'staffData.bio.date_of_birth' => 'Date of birth',
            'staffData.bio.gender' => 'gender',
            'staffData.bio.marital_status' => 'Marital status',
            'staffData.addressContact.address_line_1' => 'Address line 1',
            'staffData.addressContact.address_line_2' => 'Address line 2',
            'staffData.addressContact.post_code' => 'Post Code',
            'staffData.addressContact.city' => 'City',
            'staffData.addressContact.country' => 'Country',
            'staffData.addressContact.region' => 'Region',
            'staffData.addressContact.contact_type' => 'Contact type',
            'staffData.addressContact.contact' => 'Contact',
            'staffData.employment.file_number' => 'File number',
            'staffData.employment.hire_date' => 'Hire date',
            'staffData.qualifications.institution' =>"Institution",
            'staffData.qualifications.course' => "Course",
            'staffData.qualifications.level' => "Level",
            'staffData.qualifications.qualification' => "Qualification",
            'staffData.qualifications.qualification_number' => "Qualification Number",
            'staffData.qualifications.year' => "Graduating year",
            'staffData.rank.rank_id' => "Rank",
            'staffData.rank.start_date' => "Start date",
            'staffData.rank.end_date' => "End date",
            'staffData.unit.unit_id' => "Unit",
            'staffData.unit.start_date' => "Start date" ,
            'staffData.unit.end_date' => "End date" ,
        ];
    }

    public function messages(): array
    {
        return [
            'staffData.bio.date_of_birth.before' => 'The staff must be over 18 years', 
            'staffData.bio.date_of_birth.before_or_equal' => 'The date of birth cannot after today', 
        ];
    }
}
