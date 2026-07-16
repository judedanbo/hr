<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateApprovalDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage leave delegations');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delegator_id' => ['required', 'integer', 'exists:institution_person,id'],
            'delegate_id' => ['required', 'integer', 'different:delegator_id', 'exists:institution_person,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
