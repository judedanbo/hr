<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CredentialLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_login_is_logged_without_the_password_that_was_typed(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'almost-my-real-password',
        ]);

        $this->assertGuest();

        $activity = $this->failedLogins()->sole();
        $this->assertSame($user->email, $activity->properties->get('attributes')['email']);
        $this->assertArrayNotHasKey('password', $activity->properties->get('attributes'));
        $this->assertStringNotContainsString('almost-my-real-password', $activity->getRawOriginal('properties'));
    }

    public function test_a_failed_login_for_an_unknown_address_is_logged_without_the_password(): void
    {
        $this->post('/login', [
            'email' => 'nobody@audit.gov.gh',
            'password' => 'typed-into-the-wrong-site',
        ]);

        $this->assertGuest();

        $activity = $this->failedLogins()->sole();
        $this->assertSame(['email' => 'nobody@audit.gov.gh'], $activity->properties->get('attributes'));
        $this->assertStringNotContainsString('typed-into-the-wrong-site', $activity->getRawOriginal('properties'));
    }

    public function test_creating_a_user_does_not_log_the_password_hash(): void
    {
        $user = User::factory()->create();

        $activity = $this->activitiesAbout($user)->where('event', 'created')->sole();

        $this->assertSame($user->email, $activity->properties->get('attributes')['email']);
        $this->assertArrayNotHasKey('password', $activity->properties->get('attributes'));
        $this->assertStringNotContainsString($user->password, $activity->getRawOriginal('properties'));
    }

    public function test_changing_a_password_logs_neither_the_old_nor_the_new_hash(): void
    {
        $user = User::factory()->create(['password_change_at' => null]);
        $oldHash = $user->password;

        $this->actingAs($user)
            ->post('/change-password', [
                'current_password' => 'password',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('a-brand-new-password', $user->password));

        $activities = $this->activitiesAbout($user)->get();
        $this->assertNotEmpty($activities);

        foreach ($activities as $activity) {
            $this->assertArrayNotHasKey('password', $activity->properties->get('attributes', []));
            $this->assertArrayNotHasKey('password', $activity->properties->get('old', []));
            $this->assertStringNotContainsString($oldHash, $activity->getRawOriginal('properties'));
            $this->assertStringNotContainsString($user->password, $activity->getRawOriginal('properties'));
        }
    }

    public function test_other_changes_to_a_user_are_still_logged(): void
    {
        $user = User::factory()->create(['name' => 'Ama Mensah']);

        $user->update(['name' => 'Ama Mensah-Boateng']);

        $activity = $this->activitiesAbout($user)->where('event', 'updated')->sole();

        $this->assertSame('Ama Mensah-Boateng', $activity->properties->get('attributes')['name']);
        $this->assertSame('Ama Mensah', $activity->properties->get('old')['name']);
    }

    private function failedLogins(): \Illuminate\Database\Eloquent\Builder
    {
        return Activity::query()->where('event', 'login')->where('description', 'Failed login');
    }

    private function activitiesAbout(User $user): \Illuminate\Database\Eloquent\Builder
    {
        return Activity::query()
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->getKey());
    }
}
