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
}
