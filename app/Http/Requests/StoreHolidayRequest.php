<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create holiday');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_recurring' => $this->boolean('is_recurring'),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_year_id' => ['required', 'integer', 'exists:leave_years,id'],
            'date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = \App\Models\Holiday::query()
                        ->where('leave_year_id', $this->input('leave_year_id'))
                        ->whereDate('date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('A holiday already exists on this date for the selected leave year.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_recurring' => ['required', 'boolean'],
        ];
    }
}
