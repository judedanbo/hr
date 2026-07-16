<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreLeaveBalanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('adjust leave balance');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', 'exists:institution_person,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'leave_year_id' => ['required', 'integer', 'exists:leave_years,id'],
            'days' => ['required', 'integer', 'not_in:0', 'between:-366,366'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
