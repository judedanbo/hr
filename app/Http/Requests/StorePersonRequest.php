<?php

namespace App\Http\Requests;

use App\Enums\MaritalStatus;
use App\Enums\Gender;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
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
            'personalInformation.title'=>'string|min:1|max:10',
            'personalInformation.surname'=>'required|string|max:100',
            'personalInformation.first_name'=>'required|string|max:100',
            'personalInformation.other_names' => 'required|string|max:100',
            'personalInformation.date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->subYears(18)->format('Y-m-d'),
                'after:' . Carbon::now()->addYears(100)->format('Y-m-d')
            ],
            'personalInformation.gender' => ['required', new Enum(Gender::class)],
            'personalInformation.marital_status' => ['required', new Enum(MaritalStatus::class)],
            'contactInformation.contact_type_id' => 'required|integer',
            'contactInformation.contact' => 'required|string|max:100|unique:contacts,contact',
            'employmentInformation.staff_number' => 'required|string|max:10|unique:institution_person,staff_number',
            'employmentInformation.file_number' => 'required|string|max:10|unique:institution_person,file_number',
            'employmentInformation.hire_date' => 'required|date',
        ];
    }
}