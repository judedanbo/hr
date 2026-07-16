<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update leave type');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_evidence' => $this->boolean('requires_evidence'),
            'counts_weekends' => $this->boolean('counts_weekends'),
            'counts_holidays' => $this->boolean('counts_holidays'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $leaveTypeId = $this->route('leaveType')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('leave_types', 'code')->ignore($leaveTypeId)],
            'requires_evidence' => ['required', 'boolean'],
            'gender_restriction' => ['nullable', 'in:M,F'],
            'counts_weekends' => ['required', 'boolean'],
            'counts_holidays' => ['required', 'boolean'],
            'min_notice_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_concurrent_per_unit' => ['nullable', 'integer', 'min:1'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
