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

    public function test_cannot_delete_last_phone_contact(): void
    {
        $user = $this->staffUser();
        $phone = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000001',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $phone->id,
            ]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotNull(Contact::find($phone->id));
    }

    public function test_can_delete_phone_when_another_active_phone_exists(): void
    {
        $user = $this->staffUser();
        Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000001',
        ]);
        $secondPhone = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000002',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $secondPhone->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($secondPhone->id));
    }

    public function test_can_delete_expired_phone_even_if_no_other_active_phone(): void
    {
        $user = $this->staffUser();
        $activePhone = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000001',
        ]);
        $expiredPhone = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000099',
            'valid_end' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $expiredPhone->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($expiredPhone->id));
        $this->assertNotNull(Contact::find($activePhone->id));
    }

    public function test_can_delete_email_with_no_other_email(): void
    {
        $user = $this->staffUser();
        $email = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'only@example.org',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $email->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($email->id));
    }

    public function test_cannot_delete_audit_gov_gh_email(): void
    {
        $user = $this->staffUser();
        $email = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'me@audit.gov.gh',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $email->id,
            ]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotNull(Contact::find($email->id));
    }

    public function test_can_delete_audit_gov_gh_email_with_case_differences(): void
    {
        $user = $this->staffUser();
        $email = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'Me@AUDIT.GOV.GH',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $email->id,
            ]))
            ->assertSessionHasErrors(['contact']);

        $this->assertNotNull(Contact::find($email->id));
    }

    public function test_can_delete_non_org_email(): void
    {
        $user = $this->staffUser();
        $email = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'me@gmail.com',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $email->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($email->id));
    }

    public function test_can_delete_email_whose_domain_contains_audit_gov_gh_substring(): void
    {
        $user = $this->staffUser();

        // Subdomain — not a match
        $subdomainEmail = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'me@sub.audit.gov.gh',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $subdomainEmail->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($subdomainEmail->id));

        // audit.gov.gh as local part — also not a match
        $localPartEmail = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'audit.gov.gh@notreal.com',
        ]);

        $this->actingAs($user)
            ->delete(route('person.contact.delete', [
                'person' => $user->person_id,
                'contact' => $localPartEmail->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Contact::find($localPartEmail->id));
    }

    public function test_cannot_update_audit_gov_gh_email(): void
    {
        $user = $this->staffUser();
        $protected = Contact::create([
            'person_id' => $user->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => 'me@audit.gov.gh',
        ]);

        $this->actingAs($user)
            ->post(route('person.contact.update', [
                'person' => $user->person_id,
                'contact' => $protected->id,
            ]), [
                'contact_type' => ContactTypeEnum::EMAIL->value,
                'contact' => 'me@gmail.com',
            ])
            ->assertSessionHasErrors(['contact']);

        $this->assertSame('me@audit.gov.gh', $protected->fresh()->contact);
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
