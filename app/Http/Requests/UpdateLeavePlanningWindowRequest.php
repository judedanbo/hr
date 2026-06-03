<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateLeavePlanningWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage leave planning windows');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_after_close' => $this->boolean('allow_after_close'),
            'require_full_plan' => $this->boolean('require_full_plan'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $windowId = $this->route('leavePlanningWindow')?->id;

        return [
            'leave_year_id' => ['required', 'integer', 'exists:leave_years,id', Rule::unique('leave_planning_windows', 'leave_year_id')->ignore($windowId)],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'allow_after_close' => ['required', 'boolean'],
            'require_full_plan' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'leave_year_id.unique' => 'A planning window already exists for this leave year.',
        ];
    }
}
