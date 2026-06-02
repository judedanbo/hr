<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create leave request');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'address_during_leave' => ['required', 'string', 'max:255'],
            'contact_during_leave' => ['required', 'string', 'max:255'],
            'relieving_officer_id' => ['nullable', 'integer', 'exists:institution_person,id'],
            'leave_plan_item_id' => ['nullable', 'integer', 'exists:leave_plan_items,id'],
            'file_name' => ['nullable', 'array'],
            'file_name.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'document_title' => ['nullable', 'array'],
            'document_title.*' => ['nullable', 'string', 'max:150'],
        ];
    }
}
