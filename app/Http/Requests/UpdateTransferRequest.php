<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransferRequest extends FormRequest
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
        $startDate = Carbon::now()->subYears(50)->format('Y-m-d');
        $endDate = Carbon::now()->addYears(5)->format('Y-m-d');

        return [
            'staff_id' => 'required|exists:institution_person,id',
            'unit_id' => 'required|exists:units,id',
            'start_date' => ['date', 'after_or_equal:' . $startDate, 'nullable'],
            'end_date' => ['nullable', 'date', 'after:start_date', 'before_or_equal:' . $endDate],
            'remarks' => 'string|max:100|nullable',
        ];
    }
}
