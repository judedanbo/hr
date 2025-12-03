<?php

namespace App\Services\Staff;

use App\Contracts\Services\StaffManagementServiceInterface;
use App\Enums\Identity;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\PersonIdentity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffManagementService implements StaffManagementServiceInterface
{
    /**
     * Create a new staff member with all related records.
     *
     * @param  array  $data  Staff data including bio, employment, address, contact, qualifications, rank, unit
     * @return InstitutionPerson The created staff record
     *
     * @throws \RuntimeException If institution is not found
     */
    public function create(array $data): InstitutionPerson
    {
        return DB::transaction(function () use ($data) {
            // Create person record
            $person = Person::create($data['bio']);

            // Create Ghana Card identity if provided
            if (isset($data['bio']['ghana_card'])) {
                $person->identities()->create([
                    'id_type' => Identity::GhanaCard,
                    'id_number' => $data['bio']['ghana_card'],
                ]);
            }

            // Attach to institution
            $institution = $this->resolveInstitution($data);
            $person->institution()->attach($institution->id, $data['employment']);

            // Get the created staff record
            $staff = InstitutionPerson::where('person_id', $person->id)->first();

            // Create address if provided
            if (isset($data['address']) && ! empty($data['address'])) {
                $person->address()->create($data['address']);
            }

            // Create contact if provided
            if (isset($data['contact']) && ! empty($data['contact'])) {
                $person->contacts()->create($data['contact']);
            }

            // Create qualifications if provided
            if (isset($data['qualifications']) && ! empty($data['qualifications'])) {
                $person->qualifications()->create($data['qualifications']);
            }

            // Create initial Active status
            $staff->statuses()->create([
                'status' => 'A',
                'description' => 'Active',
                'institution_id' => $institution->id,
                'start_date' => Carbon::now(),
            ]);

            // Assign initial rank if provided
            if (isset($data['rank']) && ! empty($data['rank'])) {
                $rankData = $data['rank'];
                $jobId = $rankData['rank_id'] ?? $rankData['job_id'];
                unset($rankData['rank_id']);

                $staff->ranks()->attach($jobId, [
                    'start_date' => $rankData['start_date'] ?? Carbon::now(),
                    'end_date' => $rankData['end_date'] ?? null,
                    'remarks' => $rankData['remarks'] ?? null,
                ]);
            }

            // Assign initial unit if provided
            if (isset($data['unit']) && ! empty($data['unit']['unit_id'])) {
                $staff->units()->attach($data['unit']['unit_id'], [
                    'start_date' => $data['unit']['start_date'] ?? Carbon::now(),
                    'end_date' => $data['unit']['end_date'] ?? null,
                    'remarks' => $data['unit']['remarks'] ?? null,
                ]);
            }

            return $staff;
        });
    }

    /**
     * Update an existing staff member's information.
     *
     * @param  InstitutionPerson  $staff  The staff to update
     * @param  array  $data  Updated staff data
     * @return InstitutionPerson The updated staff record
     */
    public function update(InstitutionPerson $staff, array $data): InstitutionPerson
    {
        return DB::transaction(function () use ($staff, $data) {
            // Update personal information if provided
            if (isset($data['personalInformation'])) {
                $staff->person->update($data['personalInformation']);
            }

            // Update employment information if provided
            if (isset($data['employmentInformation'])) {
                $staff->update($data['employmentInformation']);
            }

            return $staff->fresh();
        });
    }

    /**
     * Add a contact to a person.
     */
    public function addContact(Person $person, array $data): Contact
    {
        return $person->contacts()->create($data);
    }

    /**
     * Update an existing contact.
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $contact->update($data);

        return $contact->fresh();
    }

    /**
     * Delete a contact.
     */
    public function deleteContact(Contact $contact): void
    {
        $contact->forceDelete();
    }

    /**
     * Add an address to a person, closing any previous active address.
     */
    public function addAddress(Person $person, array $data): Address
    {
        // Close any existing active address
        $person->address()->whereNull('valid_end')->update([
            'valid_end' => now(),
        ]);

        return $person->address()->create($data);
    }

    /**
     * Add an identity document to a person.
     */
    public function addIdentity(Person $person, array $data): PersonIdentity
    {
        return $person->identities()->create($data);
    }

    /**
     * Update an existing identity document.
     */
    public function updateIdentity(PersonIdentity $identity, array $data): PersonIdentity
    {
        $identity->update($data);

        return $identity->fresh();
    }

    /**
     * Delete an identity document.
     */
    public function deleteIdentity(PersonIdentity $identity): void
    {
        $identity->forceDelete();
    }

    /**
     * Resolve the institution from the data or authenticated user.
     *
     * @throws \RuntimeException If no institution can be resolved
     */
    private function resolveInstitution(array $data): Institution
    {
        // If institution_id is provided in data, use it
        if (isset($data['institution_id'])) {
            return Institution::findOrFail($data['institution_id']);
        }

        // Otherwise, try to get from authenticated user
        $institution = auth()->user()?->person?->institution()?->first();

        if (! $institution) {
            throw new \RuntimeException(
                'No institution found. User account is not associated with any institution.'
            );
        }

        return $institution;
    }
}
