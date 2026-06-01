<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateLeaveEntitlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update leave entitlement');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'min_service_months' => $this->input('min_service_months', 0),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $entitlementId = $this->route('leaveEntitlement')?->id;

        return [
            'leave_year_id' => ['required', 'integer', 'exists:leave_years,id'],
            'leave_type_id' => [
                'required',
                'integer',
                'exists:leave_types,id',
                Rule::unique('leave_entitlements')->ignore($entitlementId)->where(fn (Builder $query): Builder => $query
                    ->where('leave_year_id', $this->input('leave_year_id'))
                    ->where('job_category_id', $this->input('job_category_id'))
                    ->whereNull('deleted_at')),
            ],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'days_allowed' => ['required', 'integer', 'min:0', 'max:366'],
            'min_service_months' => ['required', 'integer', 'min:0', 'max:600'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'leave_type_id.unique' => 'An entitlement for this leave type, year and category already exists.',
        ];
    }
}
