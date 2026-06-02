<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'org_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'date_format' => ['required', 'string', 'max:50'],
            'pagination_size' => ['required', 'integer', 'min:5', 'max:100'],
            'password_change_interval_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
