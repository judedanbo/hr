<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MyProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_staff_can_upload_own_photo(): void
    {
        Storage::fake('public');
        Notification::fake();

        // Upload now lands in pending_image (not image) until an admin approves.
        // Create the permission so notification sending does not fail.
        Permission::firstOrCreate(['name' => 'approve staff photo']);

        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $staff->person_id]), [
                'image' => UploadedFile::fake()->image('me.jpg', 400, 400)->size(500),
            ])
            ->assertSessionDoesntHaveErrors();

        $person = $staff->person->fresh();

        // Photo lands as pending — approved image stays null until admin approves.
        $this->assertNotNull($person->pending_image);
        $this->assertNull($person->image);
    }

    public function test_staff_cannot_upload_photo_for_another_person(): void
    {
        Storage::fake('public');
        $me = $this->createActiveStaff();
        $someone = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $me->person_id]);

        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $someone->person_id]), [
                'image' => UploadedFile::fake()->image('me.jpg'),
            ])
            ->assertForbidden();
    }

    /**
     * Mirrors the helper in MyProfileShowTest — creates an InstitutionPerson
     * that the `active()` scope returns.
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
}
