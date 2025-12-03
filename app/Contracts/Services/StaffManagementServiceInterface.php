<?php

namespace App\Contracts\Services;

use App\Models\Address;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\PersonIdentity;

interface StaffManagementServiceInterface
{
    /**
     * Create a new staff member with all related records.
     *
     * @param  array  $data  Staff data including bio, employment, address, contact, qualifications, rank, unit
     * @return InstitutionPerson The created staff record
     */
    public function create(array $data): InstitutionPerson;

    /**
     * Update an existing staff member's information.
     *
     * @param  InstitutionPerson  $staff  The staff to update
     * @param  array  $data  Updated staff data
     * @return InstitutionPerson The updated staff record
     */
    public function update(InstitutionPerson $staff, array $data): InstitutionPerson;

    /**
     * Add a contact to a person.
     *
     * @param  Person  $person  The person to add contact to
     * @param  array  $data  Contact data (contact_type, contact)
     * @return Contact The created contact
     */
    public function addContact(Person $person, array $data): Contact;

    /**
     * Update an existing contact.
     *
     * @param  Contact  $contact  The contact to update
     * @param  array  $data  Updated contact data
     * @return Contact The updated contact
     */
    public function updateContact(Contact $contact, array $data): Contact;

    /**
     * Delete a contact.
     *
     * @param  Contact  $contact  The contact to delete
     */
    public function deleteContact(Contact $contact): void;

    /**
     * Add an address to a person, closing any previous active address.
     *
     * @param  Person  $person  The person to add address to
     * @param  array  $data  Address data
     * @return Address The created address
     */
    public function addAddress(Person $person, array $data): Address;

    /**
     * Add an identity document to a person.
     *
     * @param  Person  $person  The person to add identity to
     * @param  array  $data  Identity data (id_type, id_number)
     * @return PersonIdentity The created identity
     */
    public function addIdentity(Person $person, array $data): PersonIdentity;

    /**
     * Update an existing identity document.
     *
     * @param  PersonIdentity  $identity  The identity to update
     * @param  array  $data  Updated identity data
     * @return PersonIdentity The updated identity
     */
    public function updateIdentity(PersonIdentity $identity, array $data): PersonIdentity;

    /**
     * Delete an identity document.
     *
     * @param  PersonIdentity  $identity  The identity to delete
     */
    public function deleteIdentity(PersonIdentity $identity): void;
}
