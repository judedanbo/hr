<?php

namespace App\Http\Requests;

use App\Models\PositionStaff;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffPositionRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `can:update staff position` route
     * middleware.
     */
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
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    /**
     * A staff member holds one position at a time. Editing a row is scoped to
     * that row alone, so rather than silently closing a sibling the edit is
     * rejected when it would leave two assignments open.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assignment = $this->route('staffPosition');

            if (! $assignment instanceof PositionStaff || $this->filled('end_date')) {
                return;
            }

            $conflict = PositionStaff::query()
                ->where('staff_id', $assignment->staff_id)
                ->whereNull('end_date')
                ->whereKeyNot($assignment->getKey())
                ->exists();

            if ($conflict) {
                $validator->errors()->add(
                    'end_date',
                    'This staff member already has an open position. Set an end date, or close the other position first.'
                );
            }
        });
    }
}
