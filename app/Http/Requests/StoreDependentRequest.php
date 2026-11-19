<?php

namespace App\Http\Requests;

use App\Enums\ContactTypeEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\Nationality;
use App\Models\Dependent;
use App\Models\InstitutionPerson;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDependentRequest extends FormRequest
{
    /**
     * Read the staff id the way the integer rule and the database will.
     *
     * A JSON true, for one, passes the integer rule and is stored as 1. Turning
     * every acceptable form into the same integer here means authorization
     * and the eventual write cannot disagree about which staff member is meant.
     */
    protected function prepareForValidation(): void
    {
        $staffId = filter_var($this->input('staff_id'), FILTER_VALIDATE_INT);

        if ($staffId !== false) {
            $this->merge(['staff_id' => $staffId]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * A dependent is created against the staff member named in the payload,
     * so the create ability is checked for that staff member. A staff id that
     * cannot be read names nobody's record.
     */
    public function authorize(): bool
    {
        $staffId = $this->input('staff_id');
        $staff = is_int($staffId) ? InstitutionPerson::find($staffId) : null;

        return $this->user()->can('create', [Dependent::class, $staff]);
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
                'nullable',
            ],
            'nationality' => [new Enum(Nationality::class), 'nullable'],
            'gender' => [new Enum(GenderEnum::class), 'nullable'],
            'marital_status' => [new Enum(MaritalStatusEnum::class), 'nullable'],
            'religion' => 'string|max:40|nullable',
            'image' => 'file|image|nullable',
            'staff_id' => 'required|integer|exists:institution_person,id',
            'relation' => 'required|string|max:40',
            'contacts' => 'array|nullable',
            'contacts.*.contact_type' => ['required', new Enum(ContactTypeEnum::class)],
            'contacts.*.contact' => 'required|string|min:2|max:100',
        ];
    }
}
