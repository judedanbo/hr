<?php

namespace App\Http\Requests\Auth;

use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
{
    /**
     * Self-registration only ever issues logins on the organisation's own mail domain.
     */
    public const OFFICIAL_EMAIL_DOMAIN = 'audit.gov.gh';

    public const EMAIL_NOT_ON_RECORD = 'We could not match that email to an official @audit.gov.gh address on your staff record. Please contact HR to have your official email recorded or corrected, then try again.';

    /**
     * The staff record this registration is for, resolved once validation has passed.
     */
    private ?InstitutionPerson $staff = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staff_number' => 'required|string|max:12|exists:institution_person,staff_number',
            'surname' => 'required|string|max:30',
            'first_name' => 'required|string|max:30',
            'email' => 'required|string|max:60|regex:/[A-Za-z]+\.[A-Za-z]+/i',
            // 'phone' => 'required|string|max:15|regex:/\d{10,15}/',
        ];
    }

    /**
     * Check the request against the staff record it claims.
     *
     * Knowing a colleague's staff number and names is not proof of being that
     * colleague, so the new login's password is only ever sent to an official
     * mailbox that is already recorded as a contact of the person. A mailbox
     * typed into the form is accepted only when it is that recorded mailbox.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $staff = InstitutionPerson::query()
                ->where('staff_number', $this->input('staff_number'))
                ->with(['person.contacts', 'person.user'])
                ->first();

            if ($staff?->person === null) {
                $validator->errors()->add('staff_number', 'Staff number not found');

                return;
            }

            if (! $this->namesMatch($staff->person)) {
                $validator->errors()->add('staff_number', 'Staff number does not match the name');

                return;
            }

            if ($staff->person->user !== null) {
                $validator->errors()->add('staff_number', 'User account already exists');

                return;
            }

            if (! $this->emailIsOnStaffRecord($staff->person)) {
                $validator->errors()->add('email', self::EMAIL_NOT_ON_RECORD);

                return;
            }

            if (User::query()->where('email', $this->officialEmail())->exists()) {
                $validator->errors()->add('email', 'User account already exists');

                return;
            }

            $this->staff = $staff;
        });
    }

    /**
     * The staff record being registered. Only available once validation has passed.
     */
    public function staff(): InstitutionPerson
    {
        return $this->staff;
    }

    /**
     * The official address the visitor typed, normalised for comparison and storage.
     *
     * The form asks for the part before the @, but a full official address is
     * accepted too rather than being turned into a doubled domain.
     */
    public function officialEmail(): string
    {
        $suffix = '@' . self::OFFICIAL_EMAIL_DOMAIN;

        return Str::of((string) $this->input('email'))
            ->lower()
            ->trim()
            ->replaceMatches('/' . preg_quote($suffix, '/') . '$/', '')
            ->append($suffix)
            ->toString();
    }

    private function namesMatch(Person $person): bool
    {
        return Str::lower((string) $person->surname) === Str::lower((string) $this->input('surname'))
            && Str::lower((string) $person->first_name) === Str::lower((string) $this->input('first_name'));
    }

    /**
     * Whether the typed address is a current official email contact of the person.
     */
    private function emailIsOnStaffRecord(Person $person): bool
    {
        $typed = $this->officialEmail();

        return $person->contacts->contains(
            fn (Contact $contact): bool => $contact->valid_end === null
                && $contact->isProtectedOrgEmail()
                && Str::lower(trim((string) $contact->contact)) === $typed
        );
    }
}
