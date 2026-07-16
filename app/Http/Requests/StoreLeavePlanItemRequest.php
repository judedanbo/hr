<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLeavePlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('submit leave plan');
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
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
