<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
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
            'id' => 'required|exists:job_staff,id',
            'rank_id' => 'required|exists:jobs,id',
            'staff_id' => 'required|exists:institution_person,id',
            'start_date' => 'required|date',
            'end_date' => 'date|after:start_date|nullable',
            'remarks' => 'string|nullable',
        ];
    }
}