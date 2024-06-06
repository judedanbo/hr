<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionListRequest extends FormRequest
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
            'staff.*' => 'required|integer|exists:institution_person,id',
            'rank_id' => 'required|exists::App\Models\Job,id',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'date|after:start_date|nullable',
            'remarks' => 'string|nullable',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, mixed>
     */
    public function messages()
    {
        return [
            'staff.*.required' => 'Staff ID is required',
            'staff.*.integer' => 'Staff ID must be an integer',
            'staff.*.exists' => 'Staff ID does not exist',
            'promoteAll.rank_id.required' => 'Rank is required',
            'promoteAll.rank_id.integer' => 'Rank must be an integer',
            'promoteAll.rank_id.exists' => 'Rank does not exist',
            'promoteAll.start_date.required' => 'Start date is required',
            'promoteAll.start_date.date' => 'Start date must be a date',
            'promoteAll.start_date.before_or_equal' => 'Start date must be before or equal to today',
            'promoteAll.end_date.date' => 'End date must be a date',
            'promoteAll.end_date.after' => 'End date must be after start date',
            'promoteAll.remarks.string' => 'Remarks must be a string',
        ];
    }

    public function attributes()
    {
        return [
            'staff.*' => 'Staff ID',
            'promoteAll.rank_id' => 'Rank',
            'promoteAll.start_date' => 'Start Date',
            'promoteAll.end_date' => 'End Date',
            'promoteAll.remarks' => 'Remarks',
        ];
    }
}
