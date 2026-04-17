<?php

namespace Tests\Feature\MyProfile;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileContactMutationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_add_own_contact(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->post(route('person.contact.create', ['person' => $user->person_id]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'me@example.org',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('contacts', [
            'person_id' => $user->person_id,
            'contact' => 'me@example.org',
        ]);
    }

    public function test_staff_can_update_own_contact(): void
    {
        $user = $this->staffUser();
        $contact = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'old@example.org',
        ]);

        $this->actingAs($user)
            ->post(route('person.contact.update', [
                'person' => $user->person_id,
                'contact' => $contact->id,
            ]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'new@example.org',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('new@example.org', $contact->fresh()->contact);
    }

    public function test_staff_cannot_update_another_persons_contact(): void
    {
        $me = $this->staffUser();
        $other = InstitutionPerson::factory()->create();
        $theirContact = Contact::create([
            'person_id' => $other->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'theirs@example.org',
        ]);

        $this->actingAs($me)
            ->post(route('person.contact.update', [
                'person' => $other->person_id,
                'contact' => $theirContact->id,
            ]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'hijacked@example.org',
            ])
            ->assertForbidden();

        $this->assertSame('theirs@example.org', $theirContact->fresh()->contact);
    }

    public function test_staff_can_delete_own_contact(): void
    {
        $user = $this->staffUser();
        $contact = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'me@example.org',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $contact->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($contact->id));
    }

    private function staffUser(): User
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('staff');

        return $user;
    }
}
