<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeclineLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by LeaveRequestPolicy@decide in the controller.
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decline_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
