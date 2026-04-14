<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);

        $this->person = Person::factory()->create();
    }

    public function test_contact_index_requires_authentication(): void
    {
        $response = $this->get(route('contact.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_contact_index_requires_permission(): void
    {
        $response = $this->actingAs($this->guestUser)->get(route('contact.index'));
        $response->assertForbidden();
    }

    public function test_contact_index_displays_contacts(): void
    {
        Contact::factory()->count(3)->create(['person_id' => $this->person->id]);

        $response = $this->actingAs($this->superAdmin)->get(route('contact.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contact/Index')->has('contacts')->has('contactTypes'));
    }

    public function test_contact_store_creates_contact_for_person(): void
    {
        $contactData = [
            'person_id' => $this->person->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244123456',
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $this->assertDatabaseHas('contacts', ['person_id' => $this->person->id, 'contact' => '0244123456']);
    }

    public function test_contact_store_validates_contact_type(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('contact.store'), [
            'person_id' => $this->person->id,
            'contact_type' => 'INVALID',
            'contact' => '0244123456',
        ]);

        $response->assertSessionHasErrors('contact_type');
    }

    public function test_contact_store_validates_contact_length(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('contact.store'), [
            'person_id' => $this->person->id,
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => 'abc', // Too short
        ]);

        $response->assertSessionHasErrors('contact');
    }

    public function test_contact_update_modifies_contact(): void
    {
        $contact = Contact::factory()->create(['person_id' => $this->person->id, 'contact' => '0244111111']);

        $response = $this->actingAs($this->superAdmin)->patch(route('contact.update', ['contact' => $contact->id]), [
            'contact_type' => ContactTypeEnum::PHONE->value,
            'contact' => '0244999999',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'contact' => '0244999999']);
    }

    public function test_contact_destroy_soft_deletes_contact(): void
    {
        $contact = Contact::factory()->create(['person_id' => $this->person->id]);

        $response = $this->actingAs($this->superAdmin)->delete(route('contact.destroy', ['contact' => $contact->id]));

        $response->assertRedirect(route('contact.index'));
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }
}
