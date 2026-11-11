<?php

namespace Tests\Feature\Contacts;

use App\Enums\ContactTypeEnum;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ContactAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_record_an_official_email_on_another_persons_record(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();

        $this->actingAs($hr)
            ->post(route('person.contact.create', ['person' => $colleague->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'ama.mensah@audit.gov.gh',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'person_id' => $colleague->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'ama.mensah@audit.gov.gh',
        ]);
    }

    public function test_hr_user_cannot_change_or_remove_another_persons_contact(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $contact = Contact::factory()->create([
            'person_id' => $colleague->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'theirs@example.org',
        ]);

        $this->actingAs($hr)
            ->post(route('person.contact.update', ['person' => $colleague->id, 'contact' => $contact->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'changed@example.org',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $contact->id]))
            ->assertForbidden();

        $this->assertSame('theirs@example.org', $contact->fresh()->contact);
    }

    // -----------------------------------------------------------------------
    // Removing a wrongly recorded official email
    //
    // An @audit.gov.gh address decides where a first password is sent, so it
    // is locked: nobody may edit one, and only a holder of delete contacts,
    // which the seeders give to hr-user, may remove one.
    // -----------------------------------------------------------------------

    public function test_hr_user_can_remove_an_official_email_from_another_persons_record(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $wrong = $this->officialEmail($colleague, 'wrong.person@audit.gov.gh');

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $wrong->id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('contacts', ['id' => $wrong->id]);
    }

    public function test_removing_an_official_email_records_who_removed_which_address(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $wrong = $this->officialEmail($colleague, 'wrong.person@audit.gov.gh');

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $wrong->id]));

        $entry = Activity::query()
            ->where('subject_type', $wrong->getMorphClass())
            ->where('subject_id', $wrong->id)
            ->where('event', 'deleted')
            ->sole();

        $this->assertSame($hr->id, (int) $entry->causer_id);
        $this->assertStringContainsString('wrong.person@audit.gov.gh', json_encode($entry->properties));
    }

    public function test_an_official_email_that_hr_removed_no_longer_lets_anyone_register_with_it(): void
    {
        $hr = $this->userWithRole('hr-user');
        $staff = InstitutionPerson::factory()->create([
            'person_id' => Person::factory()->create(['first_name' => 'Ama', 'surname' => 'Mensah'])->id,
            'staff_number' => 'GAS10001',
        ]);
        $wrong = $this->officialEmail($staff->person, 'someone.else@audit.gov.gh');

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $staff->person_id, 'contact' => $wrong->id]))
            ->assertSessionHasNoErrors();

        auth()->logout();

        $this->post('/register', [
            'staff_number' => 'GAS10001',
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'email' => 'someone.else',
        ])->assertSessionHasErrors(['email' => RegisterRequest::EMAIL_NOT_ON_RECORD]);

        $this->assertDatabaseMissing('users', ['person_id' => $staff->person_id]);
    }

    public function test_hr_user_can_replace_a_wrong_official_email_with_the_right_one(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $wrong = $this->officialEmail($colleague, 'wrong.person@audit.gov.gh');

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $wrong->id]))
            ->assertSessionHasNoErrors();
        $this->actingAs($hr)
            ->post(route('person.contact.create', ['person' => $colleague->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'right.person@audit.gov.gh',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['right.person@audit.gov.gh'],
            Contact::query()->where('person_id', $colleague->id)->pluck('contact')->all()
        );
    }

    public function test_hr_user_still_cannot_edit_an_official_email(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $official = $this->officialEmail($colleague, 'ama.mensah@audit.gov.gh');

        $this->actingAs($hr)
            ->post(route('person.contact.update', ['person' => $colleague->id, 'contact' => $official->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'changed@audit.gov.gh',
            ])
            ->assertForbidden();

        $this->assertSame('ama.mensah@audit.gov.gh', $official->fresh()->contact);
    }

    public function test_update_staff_alone_does_not_remove_an_official_email(): void
    {
        $admin = $this->userWithRole('admin-user');
        $colleague = Person::factory()->create();
        $official = $this->officialEmail($colleague, 'ama.mensah@audit.gov.gh');

        $this->assertTrue($admin->can('update staff'));
        $this->assertFalse($admin->can('delete contacts'));

        $this->actingAs($admin)
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $official->id]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotSoftDeleted('contacts', ['id' => $official->id]);
    }

    public function test_the_owner_still_cannot_remove_their_own_official_email(): void
    {
        $staff = $this->userWithRole('staff');
        $official = $this->officialEmail(Person::findOrFail($staff->person_id), 'me@audit.gov.gh');

        $this->actingAs($staff)
            ->delete(route('person.contact.delete', ['person' => $staff->person_id, 'contact' => $official->id]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotSoftDeleted('contacts', ['id' => $official->id]);
    }

    public function test_staff_role_cannot_remove_a_colleagues_official_email(): void
    {
        $colleague = Person::factory()->create();
        $official = $this->officialEmail($colleague, 'ama.mensah@audit.gov.gh');

        $this->actingAs($this->userWithRole('staff'))
            ->delete(route('person.contact.delete', ['person' => $colleague->id, 'contact' => $official->id]))
            ->assertForbidden();

        $this->assertNotSoftDeleted('contacts', ['id' => $official->id]);
    }

    public function test_an_official_email_is_only_removed_from_the_record_named_in_the_request(): void
    {
        $hr = $this->userWithRole('hr-user');
        $official = $this->officialEmail(Person::factory()->create(), 'ama.mensah@audit.gov.gh');
        $somebodyElse = Person::factory()->create();

        $this->actingAs($hr)
            ->delete(route('person.contact.delete', ['person' => $somebodyElse->id, 'contact' => $official->id]))
            ->assertNotFound();

        $this->assertNotSoftDeleted('contacts', ['id' => $official->id]);
    }

    public function test_hr_user_can_remove_an_official_email_from_the_contacts_screen_too(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $official = $this->officialEmail($colleague, 'wrong.person@audit.gov.gh');
        $phone = Contact::factory()->create([
            'person_id' => $colleague->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ]);

        $this->actingAs($hr)
            ->delete(route('contact.destroy', ['contact' => $phone->id]))
            ->assertForbidden();
        $this->actingAs($hr)
            ->delete(route('contact.destroy', ['contact' => $official->id]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotSoftDeleted('contacts', ['id' => $phone->id]);
        $this->assertSoftDeleted('contacts', ['id' => $official->id]);
    }

    public function test_the_contacts_screen_offers_delete_only_where_it_will_work(): void
    {
        $hr = $this->userWithRole('hr-user');
        $colleague = Person::factory()->create();
        $official = $this->officialEmail($colleague, 'ama.mensah@audit.gov.gh');
        $phone = Contact::factory()->create([
            'person_id' => $colleague->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ]);

        $this->actingAs($hr)
            ->get(route('contact.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where(
                'contacts.data',
                function ($rows) use ($official, $phone): bool {
                    $rows = collect($rows)->keyBy('id');

                    return $rows[$official->id]['can_delete'] === true
                        && $rows[$phone->id]['can_delete'] === false;
                }
            ));
    }

    public function test_the_staff_page_marks_which_contacts_are_official_emails(): void
    {
        $hr = $this->userWithRole('hr-user');
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);
        $official = $this->officialEmail($staff->person, 'ama.mensah@audit.gov.gh');
        $phone = Contact::factory()->create([
            'person_id' => $staff->person_id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ]);

        $this->actingAs($hr)
            ->get(route('staff.show', ['staff' => $staff->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where(
                'contacts',
                function ($rows) use ($official, $phone): bool {
                    $rows = collect($rows)->keyBy('id');

                    return $rows[$official->id]['is_official_email'] === true
                        && $rows[$phone->id]['is_official_email'] === false;
                }
            ));
    }

    public function test_staff_role_cannot_add_a_contact_to_another_persons_record(): void
    {
        $staff = $this->userWithRole('staff');
        $colleague = Person::factory()->create();

        $this->assertFalse($staff->can('create contacts'));

        $this->actingAs($staff)
            ->post(route('person.contact.create', ['person' => $colleague->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'intruder@audit.gov.gh',
            ])
            ->assertForbidden();

        $this->assertSame(0, Contact::where('person_id', $colleague->id)->count());
    }

    public function test_user_can_update_own_contact(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('update contacts');
        $contact = Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'old@example.org',
        ]);

        $this->actingAs($user)
            ->patch(route('contact.update', ['contact' => $contact->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'new@example.org',
            ])
            ->assertRedirect();

        $this->assertSame('new@example.org', $contact->fresh()->contact);
    }

    public function test_user_cannot_update_another_persons_contact(): void
    {
        $me = Person::factory()->create();
        $someone = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $me->id, 'password_change_at' => now()]);
        $user->givePermissionTo('update contacts');
        $contact = Contact::factory()->create([
            'person_id' => $someone->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'theirs@example.org',
        ]);

        $this->actingAs($user)
            ->patch(route('contact.update', ['contact' => $contact->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'hijacked@example.org',
            ])
            ->assertForbidden();

        $this->assertSame('theirs@example.org', $contact->fresh()->contact);
    }

    public function test_user_cannot_delete_another_persons_contact(): void
    {
        $me = Person::factory()->create();
        $someone = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $me->id, 'password_change_at' => now()]);
        $user->givePermissionTo('delete contacts');
        $contact = Contact::factory()->create([
            'person_id' => $someone->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'theirs@example.org',
        ]);

        $this->actingAs($user)
            ->delete(route('contact.destroy', ['contact' => $contact->id]))
            ->assertForbidden();

        $this->assertNotNull($contact->fresh());
    }

    public function test_user_can_delete_own_contact(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('delete contacts');
        Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244000001',
        ]);
        $contact = Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ]);

        $this->actingAs($user)
            ->delete(route('contact.destroy', ['contact' => $contact->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_contact_destroy_route_also_blocks_deleting_last_phone(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('delete contacts');
        $contact = Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ]);

        $this->actingAs($user)
            ->delete(route('contact.destroy', ['contact' => $contact->id]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotSoftDeleted('contacts', ['id' => $contact->id]);
    }

    /**
     * This used to assert the opposite. Reaching this route already takes the
     * delete contacts permission, which is now the one that lifts the lock on
     * an official email, so the lock can no longer apply here.
     */
    public function test_contact_destroy_route_removes_an_audit_gov_gh_email_for_a_holder_of_delete_contacts(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('delete contacts');
        $contact = Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'staff@audit.gov.gh',
        ]);

        $this->actingAs($user)
            ->delete(route('contact.destroy', ['contact' => $contact->id]))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_contact_update_route_also_blocks_updating_audit_gov_gh_email(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->givePermissionTo('update contacts');
        $contact = Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'staff@audit.gov.gh',
        ]);

        $this->actingAs($user)
            ->patch(route('contact.update', ['contact' => $contact->id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'someone-else@gmail.com',
            ])
            ->assertSessionHasErrors(['contact']);

        $this->assertSame('staff@audit.gov.gh', $contact->fresh()->contact);
    }

    private function officialEmail(Person $person, string $address): Contact
    {
        return Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => $address,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['person_id' => Person::factory()->create()->id]);
        $user->assignRole($role);

        return $user;
    }
}
