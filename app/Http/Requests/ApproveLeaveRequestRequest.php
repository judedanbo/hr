<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequestRequest extends FormRequest
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
        $leaveRequest = $this->route('leaveRequest');

        return [
            'approved_days' => array_filter([
                'nullable',
                'integer',
                'min:1',
                $leaveRequest instanceof LeaveRequest ? 'max:' . $leaveRequest->requested_days : null,
            ]),
        ];
    }
}
