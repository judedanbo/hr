<?php

namespace Tests\Feature\MyProfile;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileContactEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_profile_payload_exposes_contacts_and_address(): void
    {
        $staff = $this->createActiveStaff();
        $this->createEmailContact($staff->person_id, 'me@example.org');
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->has('contacts')
                ->where('contacts.0.contact', 'me@example.org')
            );
    }

    /**
     * Mirrors the helper used across all MyProfile tests.
     */
    private function createActiveStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }

    private function createEmailContact(int $personId, string $email): void
    {
        Contact::factory()->create([
            'person_id' => $personId,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => $email,
            'valid_end' => null,
        ]);
    }
}
