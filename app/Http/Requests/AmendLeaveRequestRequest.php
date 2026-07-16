<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmendLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by LeaveRequestPolicy@amend in the controller.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
