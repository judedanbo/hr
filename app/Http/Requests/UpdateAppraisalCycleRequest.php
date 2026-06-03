<?php

namespace App\Http\Requests;

use App\Enums\AppraisalCycleStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateAppraisalCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('edit appraisal cycle');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'objective_window_start' => ['nullable', 'date'],
            'objective_window_end' => ['nullable', 'date', 'after_or_equal:objective_window_start'],
            'midyear_window_start' => ['nullable', 'date'],
            'midyear_window_end' => ['nullable', 'date', 'after_or_equal:midyear_window_start'],
            'final_window_start' => ['nullable', 'date'],
            'final_window_end' => ['nullable', 'date', 'after_or_equal:final_window_start'],
            'objectives_weight' => ['required', 'integer', 'between:0,100'],
            'competencies_weight' => ['required', 'integer', 'between:0,100'],
            'status' => ['required', Rule::enum(AppraisalCycleStatusEnum::class)],
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ((int) $this->objectives_weight + (int) $this->competencies_weight !== 100) {
                $validator->errors()->add('competencies_weight', 'Objectives and competencies weights must sum to 100.');
            }
        });
    }
}
