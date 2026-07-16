<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by LeaveRequestPolicy@resume in the controller.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'actual_return_date' => ['required', 'date'],
        ];
    }
}
