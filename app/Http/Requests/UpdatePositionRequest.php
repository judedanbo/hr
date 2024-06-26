<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('update position');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => ['required', 'integer', 'exists:positions,id'], // 'exists' rule is used to check if the value exists in the database table 'positions' under the column 'id
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
        ];
    }
}
