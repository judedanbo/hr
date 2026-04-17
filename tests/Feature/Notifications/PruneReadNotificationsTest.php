<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneReadNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_removes_old_read_notifications_and_keeps_others(): void
    {
        $user = User::factory()->create();

        $oldRead = $this->seedFor($user, readAt: now()->subDays(91));
        $recentRead = $this->seedFor($user, readAt: now()->subDays(30));
        $oldUnread = $this->seedFor($user, readAt: null, createdAt: now()->subDays(200));

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertNull(DatabaseNotification::find($oldRead->id));
        $this->assertNotNull(DatabaseNotification::find($recentRead->id));
        $this->assertNotNull(DatabaseNotification::find($oldUnread->id));
    }

    public function test_prune_respects_days_option(): void
    {
        $user = User::factory()->create();
        $read = $this->seedFor($user, readAt: now()->subDays(10));

        $this->artisan('notifications:prune', ['--days' => 5])->assertSuccessful();

        $this->assertNull(DatabaseNotification::find($read->id));
    }

    private function seedFor(User $user, $readAt, $createdAt = null): DatabaseNotification
    {
        $createdAt = $createdAt ?? now()->subDays(100);

        return DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => PhotoApprovedNotification::class,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'data' => ['title' => 'T', 'body' => 'b'],
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
