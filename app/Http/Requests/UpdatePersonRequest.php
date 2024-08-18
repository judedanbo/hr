<?php

namespace App\Http\Requests;

use App\Enums\CountryEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePersonRequest extends FormRequest
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
            'title' => 'min:1|max:10|nullable',
            'surname' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'other_names' => 'string|max:100|nullable',
            'date_of_birth' => [
                'required',
                'date',
                'before:' . Carbon::now()->format('Y-m-d'),
                'after:' . Carbon::now()->subYears(180)->format('Y-m-d'),
            ],
            'gender' => ['required', new Enum(GenderEnum::class)],
            'marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'nationality' => new Enum(CountryEnum::class),
            'ethnicity' => 'string|max:40|nullable',
            'religion' => 'string|max:40|nullable',
            'image' => 'file|image|nullable',
            'about' => 'text|nullable',
        ];
    }
}
