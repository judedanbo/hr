<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('update position');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    /**
     * The position being edited must be excluded from the uniqueness check,
     * otherwise saving without renaming fails. Soft-deleted names are free to
     * reuse.
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')
                    ->ignore($this->route('position'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
