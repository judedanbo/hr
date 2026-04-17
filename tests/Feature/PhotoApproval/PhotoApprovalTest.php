<?php

namespace Tests\Feature\PhotoApproval;

use App\Models\InstitutionPerson;
use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoPendingApprovalNotification;
use App\Notifications\PhotoRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PhotoApprovalTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

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

    private function createApproverUser(): User
    {
        Permission::firstOrCreate(['name' => 'approve staff photo']);

        $approver = User::factory()->create();
        $approver->givePermissionTo('approve staff photo');

        return $approver;
    }

    private function createStaffUser(InstitutionPerson $staff): User
    {
        return User::factory()->create(['person_id' => $staff->person_id]);
    }

    // -----------------------------------------------------------------------
    // Upload tests
    // -----------------------------------------------------------------------

    public function test_uploading_a_photo_stores_it_as_pending(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $user = $this->createStaffUser($staff);
        $approver = $this->createApproverUser();

        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $staff->person_id]), [
                'image' => UploadedFile::fake()->image('photo.jpg', 400, 400)->size(500),
            ])
            ->assertSessionDoesntHaveErrors();

        $person = $staff->person->fresh();

        // pending_image is set, approved image is NOT changed.
        $this->assertNotNull($person->pending_image);
        $this->assertNull($person->image);
        $this->assertNotNull($person->pending_image_at);

        // File exists in storage.
        Storage::disk('public')->assertExists($person->pending_image);

        // Approvers were notified.
        Notification::assertSentTo($approver, PhotoPendingApprovalNotification::class);
    }

    public function test_uploading_while_one_is_pending_replaces_the_pending_file(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $user = $this->createStaffUser($staff);
        $this->createApproverUser();

        // First upload.
        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $staff->person_id]), [
                'image' => UploadedFile::fake()->image('first.jpg', 400, 400)->size(300),
            ]);

        $firstPendingPath = $staff->person->fresh()->pending_image;
        $this->assertNotNull($firstPendingPath);

        // Second upload.
        $this->actingAs($user)
            ->post(route('person.avatar.update', ['person' => $staff->person_id]), [
                'image' => UploadedFile::fake()->image('second.jpg', 400, 400)->size(300),
            ]);

        $person = $staff->person->fresh();

        // First file deleted from storage.
        Storage::disk('public')->assertMissing($firstPendingPath);

        // New pending file is different.
        $this->assertNotNull($person->pending_image);
        $this->assertNotEquals($firstPendingPath, $person->pending_image);
        Storage::disk('public')->assertExists($person->pending_image);
    }

    // -----------------------------------------------------------------------
    // Queue access tests
    // -----------------------------------------------------------------------

    public function test_only_approvers_can_see_the_queue(): void
    {
        Storage::fake('public');

        $approver = $this->createApproverUser();
        $nonApprover = User::factory()->create();

        // Approver can access.
        $this->actingAs($approver)
            ->get(route('photo-approvals.index'))
            ->assertOk();

        // Non-approver is forbidden.
        $this->actingAs($nonApprover)
            ->get(route('photo-approvals.index'))
            ->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // Approve tests
    // -----------------------------------------------------------------------

    public function test_approve_moves_pending_into_image(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $staffUser = $this->createStaffUser($staff);
        $approver = $this->createApproverUser();

        // Give staff a pending image.
        $pendingPath = 'avatars/pending.jpg';
        Storage::disk('public')->put($pendingPath, 'fake-image-content');
        $staff->person->update([
            'pending_image' => $pendingPath,
            'pending_image_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('photo-approvals.approve', ['person' => $staff->person_id]))
            ->assertRedirect();

        $person = $staff->person->fresh();

        // Pending cleared; approved image set.
        $this->assertNull($person->pending_image);
        $this->assertNull($person->pending_image_at);
        $this->assertEquals($pendingPath, $person->image);

        // Approval metadata recorded.
        $this->assertEquals($approver->id, $person->image_approved_by);
        $this->assertNotNull($person->image_approved_at);

        // Staff user notified.
        Notification::assertSentTo($staffUser, PhotoApprovedNotification::class);
    }

    public function test_approve_deletes_previous_approved_file_from_storage(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $this->createStaffUser($staff);
        $approver = $this->createApproverUser();

        // Create both an existing approved and a pending file.
        $oldApprovedPath = 'avatars/old-approved.jpg';
        $pendingPath = 'avatars/new-pending.jpg';
        Storage::disk('public')->put($oldApprovedPath, 'old-content');
        Storage::disk('public')->put($pendingPath, 'new-content');

        $staff->person->update([
            'image' => $oldApprovedPath,
            'pending_image' => $pendingPath,
            'pending_image_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('photo-approvals.approve', ['person' => $staff->person_id]));

        // Old approved file was deleted.
        Storage::disk('public')->assertMissing($oldApprovedPath);

        // New file now lives in image field.
        $this->assertEquals($pendingPath, $staff->person->fresh()->image);
    }

    // -----------------------------------------------------------------------
    // Reject tests
    // -----------------------------------------------------------------------

    public function test_reject_clears_pending_and_removes_file(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $staffUser = $this->createStaffUser($staff);
        $approver = $this->createApproverUser();

        // Seed an approved image and a pending image.
        $approvedPath = 'avatars/approved.jpg';
        $pendingPath = 'avatars/pending.jpg';
        Storage::disk('public')->put($approvedPath, 'approved-content');
        Storage::disk('public')->put($pendingPath, 'pending-content');

        $staff->person->update([
            'image' => $approvedPath,
            'pending_image' => $pendingPath,
            'pending_image_at' => now(),
        ]);

        $this->actingAs($approver)
            ->post(route('photo-approvals.reject', ['person' => $staff->person_id]))
            ->assertRedirect();

        $person = $staff->person->fresh();

        // Pending cleared.
        $this->assertNull($person->pending_image);
        $this->assertNull($person->pending_image_at);

        // Pending file deleted; approved file untouched.
        Storage::disk('public')->assertMissing($pendingPath);
        Storage::disk('public')->assertExists($approvedPath);

        // Approved image unchanged.
        $this->assertEquals($approvedPath, $person->image);

        // Staff user notified.
        Notification::assertSentTo($staffUser, PhotoRejectedNotification::class);
    }

    // -----------------------------------------------------------------------
    // Authorization tests
    // -----------------------------------------------------------------------

    public function test_non_approver_cannot_approve_or_reject(): void
    {
        Storage::fake('public');

        $staff = $this->createActiveStaff();
        $nonApprover = User::factory()->create();

        $staff->person->update([
            'pending_image' => 'avatars/pending.jpg',
            'pending_image_at' => now(),
        ]);

        $this->actingAs($nonApprover)
            ->post(route('photo-approvals.approve', ['person' => $staff->person_id]))
            ->assertForbidden();

        $this->actingAs($nonApprover)
            ->post(route('photo-approvals.reject', ['person' => $staff->person_id]))
            ->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // Edge case tests
    // -----------------------------------------------------------------------

    public function test_approving_a_person_with_no_pending_photo_returns_422(): void
    {
        Storage::fake('public');
        Notification::fake();

        $staff = $this->createActiveStaff();
        $approver = $this->createApproverUser();

        // Ensure no pending image.
        $staff->person->update(['pending_image' => null, 'pending_image_at' => null]);

        $this->actingAs($approver)
            ->post(route('photo-approvals.approve', ['person' => $staff->person_id]))
            ->assertStatus(422);
    }
}
