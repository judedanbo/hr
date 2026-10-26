<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffPositionRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `can:create staff position` route
     * middleware, as with the transfer and promotion requests.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The staff member comes from the route, so a client-supplied staff_id is
     * not accepted.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }
}
