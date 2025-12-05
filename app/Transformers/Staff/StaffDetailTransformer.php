<?php

namespace App\Transformers\Staff;

use App\Models\InstitutionPerson;
use Carbon\Carbon;

class StaffDetailTransformer
{
    /**
     * Transform a staff record into a detailed array for display.
     */
    public function transform(InstitutionPerson $staff): array
    {
        return [
            'user' => $this->transformAuthUser(),
            'person' => $this->transformPerson($staff),
            'qualifications' => $this->transformQualifications($staff),
            'contacts' => $this->transformContacts($staff),
            'address' => $this->transformAddress($staff),
            'staff' => $this->transformStaffDetails($staff),
        ];
    }

    /**
     * Transform the authenticated user data.
     */
    protected function transformAuthUser(): array
    {
        $user = auth()->user();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'person_id' => $user->person_id,
        ];
    }

    /**
     * Transform person data.
     */
    protected function transformPerson(InstitutionPerson $staff): array
    {
        $person = $staff->person;

        return [
            'id' => $person->id,
            'name' => $person->full_name,
            'maiden_name' => $person->maiden_name,
            'dob-value' => $person->date_of_birth,
            'dob' => $person->date_of_birth?->format('d M Y'),
            'age' => $person->age . ' years old',
            'gender' => $person->gender?->label(),
            'ssn' => $person->social_security_number,
            'initials' => $person->initials,
            'nationality' => $person->nationality?->nationality(),
            'religion' => $person->religion,
            'marital_status' => $person->marital_status?->label(),
            'image' => $person->image ? '/storage/' . $person->image : null,
            'identities' => $this->transformIdentities($person->identities),
        ];
    }

    /**
     * Transform identities collection.
     */
    protected function transformIdentities($identities): ?array
    {
        if ($identities->isEmpty()) {
            return null;
        }

        return $identities->map(fn ($id) => [
            'id' => $id->id,
            'id_type' => $id->id_type,
            'id_type_display' => $id->id_type->label(),
            'id_number' => $id->id_number,
        ])->toArray();
    }

    /**
     * Transform qualifications collection.
     */
    protected function transformQualifications(InstitutionPerson $staff): array
    {
        $qualifications = $staff->person->qualifications;

        if ($qualifications->isEmpty()) {
            return [];
        }

        return $qualifications->map(fn ($qualification) => [
            'id' => $qualification->id,
            'person_id' => $qualification->person_id,
            'course' => $qualification->course,
            'institution' => $qualification->institution,
            'qualification' => $qualification->qualification,
            'qualification_number' => $qualification->qualification_number,
            'level' => $qualification->level,
            'year' => $qualification->year,
            'documents' => $qualification->documents->isNotEmpty()
                ? $qualification->documents->map(fn ($document) => [
                    'document_type' => $document->document_type,
                    'document_title' => $document->document_title,
                    'document_status' => $document->document_status,
                    'document_number' => $document->document_number,
                    'file_name' => $document->file_name,
                    'file_type' => $document->file_type,
                ])->toArray()
                : null,
        ])->toArray();
    }

    /**
     * Transform contacts collection.
     */
    protected function transformContacts(InstitutionPerson $staff): ?array
    {
        $contacts = $staff->person->contacts;

        if ($contacts->isEmpty()) {
            return null;
        }

        return $contacts->map(fn ($contact) => [
            'id' => $contact->id,
            'contact' => $contact->contact,
            'contact_type' => $contact->contact_type,
            'contact_type_dis' => $contact->contact_type->label(),
            'valid_end' => $contact->valid_end,
        ])->toArray();
    }

    /**
     * Transform address data.
     */
    protected function transformAddress(InstitutionPerson $staff): ?array
    {
        $address = $staff->person->address;

        if ($address->isEmpty()) {
            return null;
        }

        $firstAddress = $address->first();

        return [
            'id' => $firstAddress->id,
            'address_line_1' => $firstAddress->address_line_1,
            'address_line_2' => $firstAddress->address_line_2,
            'city' => $firstAddress->city,
            'region' => $firstAddress->region,
            'country' => $firstAddress->country,
            'post_code' => $firstAddress->post_code,
            'valid_end' => $firstAddress->valid_end,
        ];
    }

    /**
     * Transform staff-specific details.
     */
    protected function transformStaffDetails(InstitutionPerson $staff): array
    {
        return [
            'staff_id' => $staff->id,
            'institution_id' => $staff->institution_id,
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'hire_date_display' => $staff->hire_date?->diffForHumans(),
            'retirement_date' => $staff->person->date_of_birth?->addYears(60)->format('d M Y'),
            'retirement_date_display' => $staff->person->date_of_birth?->addYears(60)->diffForHumans(),
            'start_date' => $staff->start_date?->format('d M Y'),
            'statuses' => $this->transformStatuses($staff->statuses),
            'staff_type' => $this->transformStaffTypes($staff->type),
            'positions' => $this->transformPositions($staff->positions),
            'ranks' => $this->transformRanks($staff),
            'notes' => $this->transformNotes($staff->notes),
            'units' => $this->transformUnits($staff->units),
            'dependents' => $this->transformDependents($staff),
        ];
    }

    /**
     * Transform statuses collection.
     */
    protected function transformStatuses($statuses): ?array
    {
        if (! $statuses || $statuses->isEmpty()) {
            return null;
        }

        return $statuses->map(fn ($status) => [
            'id' => $status->id,
            'status' => $status->status,
            'status_display' => $status->status?->name,
            'description' => $status->description,
            'start_date' => $status->start_date?->format('Y-m-d'),
            'start_date_display' => $status->start_date?->format('d M Y'),
            'end_date' => $status->end_date?->format('Y-m-d'),
            'end_date_display' => $status->end_date?->format('d M Y'),
        ])->toArray();
    }

    /**
     * Transform staff types collection.
     */
    protected function transformStaffTypes($types): ?array
    {
        if (! $types || $types->isEmpty()) {
            return null;
        }

        return $types->map(fn ($type) => [
            'id' => $type->id,
            'type' => $type->staff_type,
            'type_label' => $type->staff_type->label(),
            'start_date' => $type->start_date?->format('Y-m-d'),
            'start_date_display' => $type->start_date?->format('d M Y'),
            'end_date' => $type->end_date?->format('Y-m-d'),
            'end_date_display' => $type->end_date?->format('d M Y'),
        ])->toArray();
    }

    /**
     * Transform positions collection.
     */
    protected function transformPositions($positions): ?array
    {
        if (! $positions || $positions->isEmpty()) {
            return null;
        }

        return $positions->map(fn ($position) => [
            'id' => $position->id,
            'name' => $position->name,
            'start_date' => $position->pivot->start_date,
            'end_date' => $position->pivot->end_date,
            'start_date_display' => $position->pivot->start_date
                ? Carbon::parse($position->pivot->start_date)->format('d M Y')
                : null,
            'end_date_display' => $position->pivot->end_date
                ? Carbon::parse($position->pivot->end_date)->format('d M Y')
                : null,
        ])->toArray();
    }

    /**
     * Transform ranks collection.
     */
    protected function transformRanks(InstitutionPerson $staff): array
    {
        return $staff->ranks->map(fn ($rank) => [
            'id' => $rank->id,
            'name' => $rank->name,
            'staff_name' => $staff->person->full_name,
            'staff_id' => $rank->pivot->staff_id,
            'rank_id' => $rank->pivot->job_id,
            'start_date' => $rank->pivot->start_date?->format('d M Y'),
            'start_date_unix' => $rank->pivot->start_date?->format('Y-m-d'),
            'end_date' => $rank->pivot->end_date?->format('d M Y'),
            'end_date_unix' => $rank->pivot->end_date?->format('Y-m-d'),
            'remarks' => $rank->pivot->remarks,
            'distance' => $rank->pivot->start_date?->diffForHumans(),
        ])->toArray();
    }

    /**
     * Transform notes collection.
     */
    protected function transformNotes($notes): ?array
    {
        if (! $notes || $notes->isEmpty()) {
            return null;
        }

        return $notes->map(fn ($note) => [
            'id' => $note->id,
            'note' => $note->note,
            'note_date' => $note->note_date->diffForHumans(),
            'note_date_time' => $note->note_date,
            'note_type' => $note->note_type,
            'created_by' => $note->created_by,
            'url' => $note->documents->isNotEmpty()
                ? $note->documents->map(fn ($doc) => [
                    'document_type' => $doc->document_type,
                    'document_title' => $doc->document_title,
                    'file_name' => $doc->file_name,
                    'file_type' => $doc->file_type,
                ])->toArray()
                : null,
        ])->toArray();
    }

    /**
     * Transform units collection.
     */
    protected function transformUnits($units): array
    {
        return $units->map(fn ($unit) => [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'status' => $unit->pivot->status?->label(),
            'status_color' => $unit->pivot->status?->color(),
            'department' => $unit->parent?->name,
            'department_short_name' => $unit->parent?->short_name,
            'staff_id' => $unit->pivot->staff_id,
            'start_date' => $unit->pivot->start_date?->format('d M Y'),
            'start_date_unix' => $unit->pivot->start_date?->format('Y-m-d'),
            'end_date' => $unit->pivot->end_date?->format('d M Y'),
            'end_date_unix' => $unit->pivot->end_date?->format('Y-m-d'),
            'distance' => $unit->pivot->start_date?->diffForHumans(),
            'remarks' => $unit->pivot->remarks,
            'old_data' => $unit->pivot->old_data,
        ])->toArray();
    }

    /**
     * Transform dependents collection.
     */
    protected function transformDependents(InstitutionPerson $staff): ?array
    {
        $dependents = $staff->dependents;

        if (! $dependents || $dependents->isEmpty()) {
            return null;
        }

        return $dependents->map(fn ($dep) => [
            'id' => $dep->id,
            'person_id' => $dep->person_id,
            'initials' => $dep->person->initials,
            'title' => $dep->person->title,
            'name' => $dep->person->full_name,
            'surname' => $dep->person->surname,
            'first_name' => $dep->person->first_name,
            'other_names' => $dep->person->other_names,
            'maiden_name' => $dep->person->maiden_name,
            'nationality' => $dep->person->nationality?->label(),
            'nationality_form' => $dep->person->nationality,
            'marital_status' => $dep->person->marital_status,
            'image' => $dep->person->image ? '/storage/' . $dep->person->image : null,
            'religion' => $dep->person->religion,
            'gender' => $dep->person->gender?->label(),
            'gender_form' => $dep->person->gender,
            'date_of_birth' => $dep->person->date_of_birth?->format('Y-m-d'),
            'age' => $dep->person->age . ' years old',
            'relation' => $dep->relation,
            'staff_id' => $staff->id,
            'contacts' => $dep->person->contacts->map(fn ($contact) => [
                'id' => $contact->id,
                'type' => $contact->contact_type?->label(),
                'contact_type' => $contact->contact_type?->value,
                'contact' => $contact->contact,
            ])->toArray(),
        ])->toArray();
    }
}
