<?php

namespace Tests\Feature;

use App\Http\Controllers\AuditLogController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The audit log is read by holders of view user activity and by nobody else.
 *
 * Its entries cannot be deleted through the application by anyone, a
 * super-administrator included, so the record of who did what cannot be
 * thinned out from the screen that shows it.
 */
class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
        // No role assigned

        $this->viewer = User::factory()->create(['password_change_at' => now()]);
        $this->viewer->givePermissionTo('view user activity');
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

    public function test_audit_log_show_requires_authentication(): void
    {
        $activity = $this->recordedEntry();

        $response = $this->get(route('audit-log.show', ['auditLog' => $activity->id]));

        $response->assertRedirect(route('login'));
    }

    public function test_audit_log_can_be_opened_by_a_user_holding_only_view_user_activity(): void
    {
        $activity = $this->recordedEntry();

        $this->assertSame(
            ['view user activity'],
            $this->viewer->getAllPermissions()->pluck('name')->all(),
        );

        $this->actingAs($this->viewer)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('AuditLog/Index'));

        $this->actingAs($this->viewer)
            ->get(route('audit-log.show', ['auditLog' => $activity->id]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('AuditLog/Show')
                    ->where('activity.id', $activity->id)
            );
    }

    /**
     * Being allowed to read the audit log used to be all it took to delete
     * from it.
     */
    public function test_audit_log_entry_cannot_be_deleted_by_a_user_who_can_view_the_log(): void
    {
        $activity = $this->recordedEntry();

        $response = $this->actingAs($this->viewer)
            ->delete($this->urlOf($activity));

        $this->assertModelExists($activity);
        $response->assertMethodNotAllowed();
    }

    public function test_audit_log_entry_cannot_be_deleted_by_a_super_administrator(): void
    {
        $activity = $this->recordedEntry();

        $response = $this->actingAs($this->superAdmin)
            ->delete($this->urlOf($activity));

        $this->assertModelExists($activity);
        $response->assertMethodNotAllowed();
    }

    public function test_audit_log_entry_cannot_be_deleted_by_a_user_without_permission(): void
    {
        $activity = $this->recordedEntry();

        $response = $this->actingAs($this->guestUser)
            ->delete($this->urlOf($activity));

        $this->assertModelExists($activity);
        $response->assertMethodNotAllowed();
    }

    public function test_audit_log_entry_cannot_be_deleted_by_a_guest(): void
    {
        $activity = $this->recordedEntry();

        $response = $this->delete($this->urlOf($activity));

        $this->assertModelExists($activity);
        $response->assertMethodNotAllowed();
    }

    /**
     * Covers the feature rather than one address, so that entries do not
     * become deletable or editable again through a route added elsewhere.
     */
    public function test_audit_log_is_only_reachable_through_routes_that_read(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RegisteredRoute $route) => $route->getControllerClass() === AuditLogController::class
                || str_starts_with($route->uri(), 'audit-log'));

        $this->assertFalse(Route::has('audit-log.delete'));
        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $this->assertSame(
                ['GET', 'HEAD'],
                $route->methods(),
                "The route {$route->getName()} must only read the audit log.",
            );
        }
    }

    private function recordedEntry(): Activity
    {
        return Activity::create([
            'log_name' => 'default',
            'description' => 'Test activity that must stay on record',
        ]);
    }

    /**
     * The address of one entry, where a delete request used to be accepted.
     */
    private function urlOf(Activity $activity): string
    {
        return '/audit-log/' . $activity->id;
    }
}
