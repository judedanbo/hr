<?php

namespace Tests\Feature\Contacts;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_contact_destroy_route_also_blocks_deleting_audit_gov_gh_email(): void
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
            ->assertSessionHasErrors(['contact']);

        $this->assertNotSoftDeleted('contacts', ['id' => $contact->id]);
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['person_id' => Person::factory()->create()->id]);
        $user->assignRole($role);

        return $user;
    }
}
