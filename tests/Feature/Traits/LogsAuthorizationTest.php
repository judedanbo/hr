<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use App\Traits\LogsAuthorization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LogsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test class that uses the trait
        $this->testClass = new class
        {
            use LogsAuthorization;

            public function callAuthorizeWithLog(string $permission, string $message, $model = null)
            {
                return $this->authorizeWithLog($permission, $message, $model);
            }

            public function callLogSuccess(string $message, $model = null)
            {
                return $this->logSuccess($message, $model);
            }

            public function callLogFailedAuthorization(string $permission)
            {
                return $this->logFailedAuthorization($permission);
            }
        };
    }

    /**
     * Get the last activity by ID (more reliable than created_at for same-second logs)
     */
    private function getLastActivityById(): ?Activity
    {
        return Activity::query()->orderByDesc('id')->first();
    }

    public function test_authorize_with_log_allows_authorized_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('test-permission', fn () => true);

        $result = $this->testClass->callAuthorizeWithLog('test-permission', 'did something');

        $this->assertNull($result);

        $activity = $this->getLastActivityById();
        $this->assertEquals('authorization_success', $activity->event);
        $this->assertEquals('success', $activity->properties['result']);
        $this->assertEquals('did something', $activity->description);
    }

    public function test_authorize_with_log_denies_unauthorized_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Gate::define('test-permission', fn () => false);

        $result = $this->testClass->callAuthorizeWithLog('test-permission', 'did something');

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);

        $activity = $this->getLastActivityById();
        $this->assertEquals('authorization_failed', $activity->event);
        $this->assertEquals('failed', $activity->properties['result']);
        $this->assertEquals('test-permission', $activity->properties['permission']);
    }

    public function test_log_success_records_activity(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->testClass->callLogSuccess('performed an action');

        $activity = $this->getLastActivityById();
        $this->assertEquals('success', $activity->event);
        $this->assertEquals('success', $activity->properties['result']);
        $this->assertEquals('performed an action', $activity->description);
        $this->assertEquals($user->id, $activity->causer_id);
    }

    public function test_log_success_attaches_model_when_provided(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        $this->actingAs($user);

        $this->testClass->callLogSuccess('viewed user', $targetUser);

        $activity = $this->getLastActivityById();
        $this->assertEquals($targetUser->id, $activity->subject_id);
        $this->assertEquals(User::class, $activity->subject_type);
    }

    public function test_log_failed_authorization_records_failure(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->testClass->callLogFailedAuthorization('forbidden-permission');

        $activity = $this->getLastActivityById();
        $this->assertEquals('authorization_failed', $activity->event);
        $this->assertEquals('failed', $activity->properties['result']);
        $this->assertEquals('forbidden-permission', $activity->properties['permission']);
        $this->assertStringContainsString('attempted: forbidden-permission', $activity->description);
    }

    public function test_activity_log_includes_request_properties(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->testClass->callLogSuccess('test action');

        $activity = $this->getLastActivityById();
        $this->assertArrayHasKey('user_ip', $activity->properties->toArray());
        $this->assertArrayHasKey('user_agent', $activity->properties->toArray());
    }
}
