<?php

namespace Tests\Feature\Person;

use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The staff role holds the upload avatar permission so that staff can manage
 * their own photo. It must not double as a licence to change anyone else's.
 */
class PersonAvatarAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Notification::fake();
    }

    public function test_staff_role_holds_the_upload_avatar_permission(): void
    {
        $this->assertTrue($this->staffUser()->can('upload avatar'));
    }

    public function test_staff_cannot_upload_a_photo_for_another_person(): void
    {
        $colleague = $this->colleague();

        $this->actingAs($this->staffUser())
            ->post(route('person.avatar.update', ['person' => $colleague->id]), [
                'image' => UploadedFile::fake()->image('not-them.jpg', 400, 400),
            ])
            ->assertForbidden();

        $this->assertNull($colleague->fresh()->pending_image);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_staff_cannot_delete_another_persons_photo(): void
    {
        $colleague = $this->colleague(['image' => 'avatars/colleague.jpg']);

        $this->actingAs($this->staffUser())
            ->delete(route('person.avatar.delete', ['person' => $colleague->id]))
            ->assertForbidden();

        $this->assertSame('avatars/colleague.jpg', $colleague->fresh()->image);
    }

    public function test_staff_can_upload_and_delete_their_own_photo(): void
    {
        $user = $this->staffUser();
        $person = Person::findOrFail($user->person_id);
        $person->update(['image' => 'avatars/me.jpg']);

        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $person->id]), [
                'image' => UploadedFile::fake()->image('me.jpg', 400, 400),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull($person->fresh()->pending_image);

        $this->actingAs($user)
            ->delete(route('person.avatar.delete', ['person' => $person->id]))
            ->assertRedirect();

        $this->assertNull($person->fresh()->image);
    }

    public function test_user_with_update_staff_can_change_another_persons_photo(): void
    {
        $colleague = $this->colleague(['image' => 'avatars/colleague.jpg']);
        $admin = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $admin->givePermissionTo('update staff');

        $this->actingAs($admin)
            ->post(route('person.avatar.update', ['person' => $colleague->id]), [
                'image' => UploadedFile::fake()->image('colleague.jpg', 400, 400),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull($colleague->fresh()->pending_image);

        $this->actingAs($admin)
            ->delete(route('person.avatar.delete', ['person' => $colleague->id]))
            ->assertRedirect();

        $this->assertNull($colleague->fresh()->image);
    }

    /**
     * A signed-in member of staff holding only the staff role.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    /**
     * Another member of staff's person record.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function colleague(array $attributes = []): Person
    {
        $person = Person::factory()->create($attributes);
        $this->activeStaff($person);

        return $person;
    }

    private function activeStaff(?Person $person = null): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create(
            $person ? ['person_id' => $person->id] : []
        );
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }
}
