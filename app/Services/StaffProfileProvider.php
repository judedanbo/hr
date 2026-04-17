<?php

namespace App\Services;

use App\Enums\QualificationLevelEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use Carbon\Carbon;

final class StaffProfileProvider
{
    /**
     * Build the profile payload consumed by both the admin Staff/NewShow page
     * and the self-service MyProfile page.
     *
     * @return array{person: array, qualifications: array, contacts: array, address: array|null, staff: array}|null
     */
    public function forPerson(int $personId): ?array
    {
        $staff = InstitutionPerson::query()
            ->with([
                'person' => function ($query) {
                    $query->with([
                        'address' => fn ($q) => $q->whereNull('valid_end'),
                        'contacts',
                        'identities' => fn ($q) => $q->withTrashed(),
                        'qualifications',
                    ]);
                },
                'units' => fn ($query) => $query->with(['institution', 'parent']),
                'ranks',
                'dependents.person',
                'statuses',
                'notes.documents',
                'positions' => fn ($query) => $query->withTrashed(),
            ])
            ->active()
            ->where('person_id', $personId)
            ->first();

        if (! $staff) {
            return null;
        }

        return [
            'person' => $this->mapPerson($staff),
            'qualifications' => $this->mapQualifications($staff->person->id),
            'contacts' => $this->mapContacts($staff),
            'address' => $this->mapAddress($staff),
            'staff' => $this->mapStaff($staff),
        ];
    }

    private function mapPerson(InstitutionPerson $staff): array
    {
        return [
            'id' => $staff->person->id,
            'name' => $staff->person->full_name,
            'maiden_name' => $staff->person->maiden_name,
            'dob-value' => $staff->person->date_of_birth,
            'dob' => $staff->person->date_of_birth?->format('d M Y'),
            'age' => $staff->person->age . ' years old',
            'gender' => $staff->person->gender?->label(),
            'ssn' => $staff->person->social_security_number,
            'initials' => $staff->person->initials,
            'nationality' => $staff->person->nationality?->nationality(),
            'religion' => $staff->person->religion,
            'marital_status' => $staff->person->marital_status?->label(),
            'image' => $staff->person->image ? '/storage/' . $staff->person->image : null,
            'pending_image' => $staff->person->pending_image ? '/storage/' . $staff->person->pending_image : null,
            'pending_image_at' => $staff->person->pending_image_at?->diffForHumans(),
            'identities' => $staff->person->identities->map(fn ($id) => [
                'id' => $id->id,
                'id_type' => $id->id_type,
                'id_type_display' => $id->id_type->label(),
                'id_number' => $id->id_number,
            ])->all(),
        ];
    }

    private function mapQualifications(int $personId): array
    {
        return Qualification::query()
            ->where('person_id', $personId)
            ->visibleTo(auth()->user(), $personId)
            ->with('documents')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'person_id' => $q->person_id,
                'course' => $q->course,
                'institution' => $q->institution,
                'qualification' => $q->qualification,
                'qualification_number' => $q->qualification_number,
                'level' => $q->level ? QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level : null,
                'year' => $q->year,
                'status' => $q->status?->label(),
                'status_color' => $q->status?->color(),
                'can_edit' => $q->canBeEditedBy(auth()->user()),
                'can_delete' => $q->canBeDeletedBy(auth()->user()),
                'documents' => $q->documents->count() > 0 ? $q->documents->map(fn ($d) => [
                    'id' => $d->id,
                    'document_type' => $d->document_type,
                    'document_title' => $d->document_title,
                    'document_status' => $d->document_status,
                    'document_number' => $d->document_number,
                    'file_name' => $d->file_name,
                    'file_type' => $d->file_type,
                ])->all() : null,
            ])
            ->all();
    }

    private function mapContacts(InstitutionPerson $staff): array
    {
        return $staff->person->contacts->map(fn ($c) => [
            'id' => $c->id,
            'contact' => $c->contact,
            'contact_type' => $c->contact_type,
            'contact_type_dis' => $c->contact_type->label(),
            'valid_end' => $c->valid_end,
        ])->all();
    }

    private function mapAddress(InstitutionPerson $staff): ?array
    {
        $address = $staff->person->address->first();
        if (! $address) {
            return null;
        }

        return [
            'id' => $address->id,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'region' => $address->region,
            'country' => $address->country,
            'post_code' => $address->post_code,
            'valid_end' => $address->valid_end,
        ];
    }

    private function mapStaff(InstitutionPerson $staff): array
    {
        return [
            'staff_id' => $staff->id,
            'institution_id' => $staff->institution_id,
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'old_staff_number' => $staff->old_staff_number,
            'hire_date' => $staff->hire_date?->format('d M Y'),
            'hire_date_display' => $staff->hire_date?->diffForHumans(),
            'retirement_date' => $staff->retirement_date_formatted,
            'retirement_date_display' => $staff->retirement_date_diff,
            'start_date' => $staff->start_date?->format('d M Y'),
            'statuses' => $staff->statuses?->map(fn ($s) => [
                'id' => $s->id,
                'status' => $s->status,
                'status_display' => $s->status?->name,
                'description' => $s->description,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'start_date_display' => $s->start_date?->format('d M Y'),
                'end_date' => $s->end_date?->format('Y-m-d'),
                'end_date_display' => $s->end_date?->format('d M Y'),
            ])->all(),
            'staff_type' => $staff->type?->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->staff_type,
                'type_label' => $t->staff_type->label(),
                'start_date' => $t->start_date?->format('Y-m-d'),
                'start_date_display' => $t->start_date?->format('d M Y'),
                'end_date' => $t->end_date?->format('Y-m-d'),
                'end_date_display' => $t->end_date?->format('d M Y'),
            ])->all(),
            'positions' => $staff->positions?->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'start_date' => $p->pivot->start_date,
                'end_date' => $p->pivot->end_date,
                'start_date_display' => $p->pivot->start_date ? Carbon::parse($p->pivot->start_date)->format('d M Y') : null,
                'end_date_display' => $p->pivot->end_date ? Carbon::parse($p->pivot->end_date)->format('d M Y') : null,
            ])->all(),
            'ranks' => $staff->ranks->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'staff_name' => $staff->person->full_name,
                'staff_id' => $r->pivot->staff_id,
                'rank_id' => $r->pivot->job_id,
                'start_date' => $r->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $r->pivot->start_date?->format('Y-m-d'),
                'end_date' => $r->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $r->pivot->end_date?->format('Y-m-d'),
                'remarks' => $r->pivot->remarks,
                'distance' => $r->pivot->start_date?->diffForHumans(),
            ])->all(),
            'notes' => $staff->notes->count() > 0 ? $staff->notes->map(fn ($n) => [
                'id' => $n->id,
                'note' => $n->note,
                'note_date' => $n->note_date->diffForHumans(),
                'note_date_time' => $n->note_date,
                'note_type' => $n->note_type,
                'created_by' => $n->created_by,
                'url' => $n->documents->count() > 0 ? $n->documents->map(fn ($d) => [
                    'document_type' => $d->document_type,
                    'document_title' => $d->document_title,
                    'file_name' => $d->file_name,
                    'file_type' => $d->file_type,
                ])->all() : null,
            ])->all() : null,
            'units' => $staff->units->map(fn ($u) => [
                'unit_id' => $u->id,
                'unit_name' => $u->name,
                'status' => $u->pivot->status?->label(),
                'status_color' => $u->pivot->status?->color(),
                'department' => $u->parent?->name,
                'department_short_name' => $u->parent?->short_name,
                'staff_id' => $u->pivot->staff_id,
                'start_date' => $u->pivot->start_date?->format('d M Y'),
                'start_date_unix' => $u->pivot->start_date?->format('Y-m-d'),
                'end_date' => $u->pivot->end_date?->format('d M Y'),
                'end_date_unix' => $u->pivot->end_date?->format('Y-m-d'),
                'distance' => $u->pivot->start_date?->diffForHumans(),
                'remarks' => $u->pivot->remarks,
                'old_data' => $u->pivot->old_data,
            ])->all(),
            'dependents' => $staff->dependents ? $staff->dependents->map(fn ($d) => [
                'id' => $d->id,
                'person_id' => $d->person_id,
                'initials' => $d->person->initials,
                'title' => $d->person->title,
                'name' => $d->person->full_name,
                'surname' => $d->person->surname,
                'first_name' => $d->person->first_name,
                'other_names' => $d->person->other_names,
                'maiden_name' => $d->person->maiden_name,
                'nationality' => $d->person->nationality?->label(),
                'nationality_form' => $d->person->nationality,
                'marital_status' => $d->person->marital_status,
                'image' => $d->person->image ? '/storage/' . $d->person->image : null,
                'religion' => $d->person->religion,
                'gender' => $d->person->gender?->label(),
                'gender_form' => $d->person->gender,
                'date_of_birth' => $d->person->date_of_birth?->format('Y-m-d'),
                'age' => $d->person->age . ' years old',
                'relation' => $d->relation,
                'staff_id' => $staff->id,
                'contacts' => $d->person->contacts->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => $c->contact_type?->label(),
                    'contact' => $c->contact,
                ])->all(),
            ])->all() : null,
        ];
    }
}
