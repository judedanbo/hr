<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchViewModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMultiRoleStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'string', 'in:staff,other'],
        ];
    }
}
