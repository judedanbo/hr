<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
        // No role assigned
    }

    public function test_audit_log_index_requires_authentication(): void
    {
        $response = $this->get(route('audit-log.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_audit_log_index_requires_permission(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('audit-log.index'));

        $response->assertForbidden();
    }

    public function test_audit_log_index_displays_activities(): void
    {
        // Create some activity logs
        activity()->log('Test activity 1');
        activity()->log('Test activity 2');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('AuditLog/Index')
                ->has('activities')
                ->has('filterOptions')
        );
    }

    public function test_audit_log_index_can_filter_by_log_name(): void
    {
        activity()
            ->useLog('custom-log')
            ->log('Custom log activity');

        activity()
            ->useLog('default')
            ->log('Default log activity');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.index', ['log_name' => 'custom-log']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('AuditLog/Index')
                ->has('activities.data', 1)
        );
    }

    public function test_audit_log_index_can_filter_by_date_range(): void
    {
        // Create an old activity (outside filter range)
        $oldActivity = Activity::create([
            'log_name' => 'default',
            'description' => 'Old activity to exclude',
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        // Filter to only show activities from last 20 days
        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.index', [
                'date_from' => now()->subDays(20)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('AuditLog/Index')
                ->has('activities')
                ->where('activities.data', fn ($data) => collect($data)->doesntContain('description', 'Old activity to exclude'))
        );
    }

    public function test_audit_log_show_requires_permission(): void
    {
        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'Test activity',
        ]);

        $response = $this->actingAs($this->guestUser)
            ->get(route('audit-log.show', ['auditLog' => $activity->id]));

        $response->assertForbidden();
    }

    public function test_audit_log_show_displays_activity_details(): void
    {
        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'Test activity for show',
            'event' => 'created',
            'properties' => ['key' => 'value'],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.show', ['auditLog' => $activity->id]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('AuditLog/Show')
                ->has('activity')
                ->where('activity.id', $activity->id)
                ->where('activity.description', 'Test activity for show')
        );
    }

    public function test_audit_log_delete_requires_permission(): void
    {
        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'Test activity to delete',
        ]);

        $response = $this->actingAs($this->guestUser)
            ->delete(route('audit-log.delete', ['auditLog' => $activity->id]));

        $response->assertForbidden();
    }

    public function test_audit_log_delete_removes_activity(): void
    {
        $activity = Activity::create([
            'log_name' => 'default',
            'description' => 'Test activity to delete',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('audit-log.delete', ['auditLog' => $activity->id]));

        $response->assertRedirect(route('audit-log.index'));
        $this->assertDatabaseMissing('activity_log', ['id' => $activity->id]);
    }
}
