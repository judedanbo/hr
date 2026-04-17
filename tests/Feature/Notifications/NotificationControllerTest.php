<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoPendingApprovalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_index_returns_only_current_users_notifications(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->seedNotification($userA, PhotoApprovedNotification::class, ['body' => 'A']);
        $this->seedNotification($userB, PhotoApprovedNotification::class, ['body' => 'B']);

        $response = $this->actingAs($userA)->get(route('notifications.index'))->assertOk();

        $notifications = $response->viewData('page')['props']['notifications']['data'];
        $this->assertCount(1, $notifications);
        $this->assertSame('A', $notifications[0]['body']);
    }

    public function test_index_filters_by_unread_status(): void
    {
        $user = User::factory()->create();
        $unread = $this->seedNotification($user, PhotoApprovedNotification::class);
        $read = $this->seedNotification($user, PhotoApprovedNotification::class);
        $read->markAsRead();

        $response = $this->actingAs($user)->get(route('notifications.index', ['status' => 'unread']))->assertOk();

        $ids = array_column($response->viewData('page')['props']['notifications']['data'], 'id');
        $this->assertContains($unread->id, $ids);
        $this->assertNotContains($read->id, $ids);
    }

    public function test_index_filters_by_type(): void
    {
        $user = User::factory()->create();
        $this->seedNotification($user, PhotoApprovedNotification::class);
        $pending = $this->seedNotification($user, PhotoPendingApprovalNotification::class);

        $response = $this->actingAs($user)
            ->get(route('notifications.index', ['type' => PhotoPendingApprovalNotification::class]))
            ->assertOk();

        $data = $response->viewData('page')['props']['notifications']['data'];
        $this->assertCount(1, $data);
        $this->assertSame($pending->id, $data[0]['id']);
    }

    public function test_recent_returns_unread_count_and_up_to_ten_items(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 12) as $i) {
            $this->seedNotification($user, PhotoApprovedNotification::class, ['body' => "msg-{$i}"]);
        }

        $response = $this->actingAs($user)->getJson(route('notifications.recent'))->assertOk();

        $response->assertJsonPath('unread_count', 12);
        $this->assertCount(10, $response->json('items'));
    }

    public function test_mark_read_marks_a_notification_and_rejects_foreign_ids(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->seedNotification($user, PhotoApprovedNotification::class);
        $theirs = $this->seedNotification($other, PhotoApprovedNotification::class);

        $this->actingAs($user)
            ->postJson(route('notifications.read', ['notification' => $mine->id]))
            ->assertOk();

        $this->assertNotNull($mine->fresh()->read_at);

        $this->actingAs($user)
            ->postJson(route('notifications.read', ['notification' => $theirs->id]))
            ->assertNotFound();

        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_mark_all_read_clears_all_unread_for_current_user_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mineA = $this->seedNotification($user, PhotoApprovedNotification::class);
        $mineB = $this->seedNotification($user, PhotoApprovedNotification::class);
        $theirs = $this->seedNotification($other, PhotoApprovedNotification::class);

        $this->actingAs($user)->postJson(route('notifications.read-all'))->assertOk();

        $this->assertNotNull($mineA->fresh()->read_at);
        $this->assertNotNull($mineB->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_destroy_deletes_notification_and_rejects_foreign_ids(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->seedNotification($user, PhotoApprovedNotification::class);
        $theirs = $this->seedNotification($other, PhotoApprovedNotification::class);

        $this->actingAs($user)
            ->deleteJson(route('notifications.destroy', ['notification' => $mine->id]))
            ->assertOk();

        $this->assertNull(DatabaseNotification::find($mine->id));

        $this->actingAs($user)
            ->deleteJson(route('notifications.destroy', ['notification' => $theirs->id]))
            ->assertNotFound();

        $this->assertNotNull(DatabaseNotification::find($theirs->id));
    }

    /**
     * @param  class-string  $type
     * @param  array<string, mixed>  $data
     */
    private function seedNotification(User $user, string $type, array $data = []): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'data' => array_merge(['title' => 'Test', 'body' => 'body', 'icon' => 'bell', 'url' => '/'], $data),
            'read_at' => null,
        ]);
    }
}
