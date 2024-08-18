<?php

namespace App\Http\Requests;

use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\Nationality;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDependentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => 'string|min:1|max:10|nullable',
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'other_names' => 'string|max:100|nullable',
            'date_of_birth' => [
                'date',
                'before_or_equal:' . Carbon::now()->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(150)->format('Y-m-d'),
            ],
            'nationality' => [new Enum(Nationality::class), 'nullable'],
            'gender' => ['required', new Enum(GenderEnum::class)],
            'marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'religion' => 'string|max:40|nullable',
            'image' => 'file|image|nullable',
            'staff_id' => 'required|integer|exists:institution_person,id',
            'relation' => 'required|string|max:40',
        ];
    }
}
