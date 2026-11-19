<?php

namespace Tests\Feature;

use App\Console\Commands\ScrubFailedLoginPasswords;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Failed sign-ins used to store the typed password beside the email. New
 * entries no longer do (see CredentialLoggingTest); this command removes it
 * from the entries stored before that.
 */
class ScrubFailedLoginPasswordsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const TYPED = 'not-a-real-password';

    public function test_it_removes_the_password_and_keeps_the_email(): void
    {
        $entry = $this->failedLoginStoredWithPassword('someone@example.test');

        $this->artisan('app:scrub-failed-login-passwords')
            ->expectsOutput('Scrubbed: 1')
            ->assertSuccessful();

        $this->assertSame(
            ['attributes' => ['email' => 'someone@example.test']],
            $entry->fresh()->properties->toArray(),
        );
        $this->assertStringNotContainsString('password', $this->stored($entry));
    }

    public function test_it_is_safe_to_run_twice(): void
    {
        $entry = $this->failedLoginStoredWithPassword('someone@example.test');

        $this->artisan('app:scrub-failed-login-passwords')
            ->expectsOutput('Scrubbed: 1')
            ->assertSuccessful();
        $afterFirstRun = $this->stored($entry);

        $this->artisan('app:scrub-failed-login-passwords')
            ->expectsOutput('Scrubbed: 0')
            ->assertSuccessful();

        $this->assertSame($afterFirstRun, $this->stored($entry));
    }

    public function test_dry_run_counts_the_entries_without_changing_them(): void
    {
        $first = $this->failedLoginStoredWithPassword('someone@example.test');
        $second = $this->failedLoginStoredWithPassword('someone.else@example.test');
        $before = [$this->stored($first), $this->stored($second)];

        $this->artisan('app:scrub-failed-login-passwords', ['--dry-run' => true])
            ->expectsOutput('Would scrub: 2')
            ->expectsOutput('Dry run: nothing was changed.')
            ->assertSuccessful();

        $this->assertSame($before, [$this->stored($first), $this->stored($second)]);
        $this->assertStringContainsString(self::TYPED, $this->stored($first));
    }

    public function test_it_changes_nothing_else_about_the_entry(): void
    {
        $this->travelTo(now()->subYear());
        $entry = activity()
            ->withProperties([
                'attributes' => ['email' => 'someone@example.test', 'password' => self::TYPED],
                'user_ip' => '192.0.2.10',
            ])
            ->event('login')
            ->log('Failed login');
        $this->travelBack();
        $before = $entry->fresh();

        $this->artisan('app:scrub-failed-login-passwords')->assertSuccessful();

        $after = $entry->fresh();
        $this->assertEquals(
            ['attributes' => ['email' => 'someone@example.test'], 'user_ip' => '192.0.2.10'],
            $after->properties->toArray(),
        );
        $this->assertSame(
            Arr::except($before->getAttributes(), 'properties'),
            Arr::except($after->getAttributes(), 'properties'),
        );
    }

    public function test_it_only_touches_failed_logins_that_hold_a_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => self::TYPED]);
        $failedLoginAsRecordedNow = Activity::query()
            ->where('event', 'login')
            ->where('description', 'Failed login')
            ->sole();
        $successfulLogin = activity()
            ->causedBy($user)
            ->event('login')
            ->log('Successfully Logged in');
        $changeToAUser = activity()
            ->performedOn($user)
            ->event('updated')
            ->withProperties(['attributes' => ['password' => 'a-stored-hash'], 'old' => ['password' => 'an-older-hash']])
            ->log('updated');

        $entries = [$failedLoginAsRecordedNow, $successfulLogin, $changeToAUser];
        $before = array_map($this->stored(...), $entries);

        $this->artisan('app:scrub-failed-login-passwords')
            ->expectsOutput('Scrubbed: 0')
            ->assertSuccessful();

        $this->assertSame($before, array_map($this->stored(...), $entries));
    }

    public function test_it_scrubs_more_entries_than_one_statement_changes(): void
    {
        $entries = ScrubFailedLoginPasswords::CHUNK + 1;

        Activity::query()->insert(
            collect(range(1, $entries))
                ->map(fn (int $number) => [
                    'log_name' => 'default',
                    'description' => 'Failed login',
                    'event' => 'login',
                    'properties' => json_encode([
                        'attributes' => ['email' => "someone{$number}@example.test", 'password' => self::TYPED],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all()
        );

        $this->artisan('app:scrub-failed-login-passwords')
            ->expectsOutput("Scrubbed: {$entries}")
            ->assertSuccessful();

        $recorded = Activity::query()
            ->where('event', 'login')
            ->get()
            ->map(fn (Activity $entry) => array_keys($entry->properties->get('attributes')));

        $this->assertCount($entries, $recorded);
        $this->assertSame([['email']], $recorded->unique()->values()->all());
    }

    /**
     * A failed sign-in as it was recorded before the password was left out.
     */
    private function failedLoginStoredWithPassword(string $email): Activity
    {
        return activity()
            ->withProperties(['attributes' => ['email' => $email, 'password' => self::TYPED]])
            ->event('login')
            ->log('Failed login');
    }

    /**
     * The properties column as the database holds it. Null when the entry has
     * no properties.
     */
    private function stored(Activity $entry): ?string
    {
        return $entry->fresh()->getRawOriginal('properties');
    }
}
