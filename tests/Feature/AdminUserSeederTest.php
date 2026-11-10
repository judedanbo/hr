<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_super_administrator_and_prints_the_password_once(): void
    {
        $output = $this->runSeeder();

        $admin = $this->admin();
        $this->assertTrue($admin->hasRole('super-administrator'));

        $password = $this->passwordPrintedIn($output);
        $this->assertGreaterThanOrEqual(24, strlen($password));
        $this->assertTrue(Hash::check($password, $admin->password));
    }

    public function test_every_run_on_a_new_database_produces_a_different_password(): void
    {
        $first = $this->passwordPrintedIn($this->runSeeder());

        $this->admin()->delete();

        $second = $this->passwordPrintedIn($this->runSeeder());

        $this->assertNotSame($first, $second);
    }

    public function test_the_new_account_must_change_its_password_at_first_sign_in(): void
    {
        $password = $this->passwordPrintedIn($this->runSeeder());

        $this->assertNull($this->admin()->password_change_at);

        $this->post('/login', ['email' => AdminUserSeeder::EMAIL, 'password' => $password])
            ->assertRedirect(route('change-password.index'));
        $this->assertAuthenticatedAs($this->admin());

        $this->get(route('staff.index'))->assertRedirect(route('change-password.index'));
    }

    public function test_running_it_again_keeps_the_password_the_account_already_has(): void
    {
        $this->runSeeder();
        $hash = $this->admin()->password;

        $output = $this->runSeeder();

        $this->assertSame($hash, $this->admin()->password);
        $this->assertStringContainsString('already exists', $output);
        $this->assertStringNotContainsString('one-time password', $output);
        $this->assertSame(1, User::query()->where('email', AdminUserSeeder::EMAIL)->count());
    }

    public function test_an_account_that_already_exists_is_given_the_role_and_keeps_its_password(): void
    {
        $existing = User::factory()->create(['email' => AdminUserSeeder::EMAIL]);
        $hash = $existing->password;

        $output = $this->runSeeder();

        $existing->refresh();
        $this->assertTrue($existing->hasRole('super-administrator'));
        $this->assertSame($hash, $existing->password);
        $this->assertNotNull($existing->password_change_at);
        $this->assertStringNotContainsString('one-time password', $output);
    }

    public function test_the_seeder_holds_no_password_of_its_own(): void
    {
        $source = file_get_contents(database_path('seeders/AdminUserSeeder.php'));

        $this->assertDoesNotMatchRegularExpression('/(bcrypt|Hash::make)\(\s*[\'"]/', $source);
        $this->assertDoesNotMatchRegularExpression('/[\'"]password[\'"]\s*=>\s*[\'"]/', $source);
    }

    private function runSeeder(): string
    {
        Artisan::call('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);

        return Artisan::output();
    }

    private function admin(): User
    {
        return User::query()->where('email', AdminUserSeeder::EMAIL)->sole();
    }

    /**
     * The password is the last word of the line that announces it.
     */
    private function passwordPrintedIn(string $output): string
    {
        $this->assertSame(1, preg_match('/one-time password, shown only now: (\S+)/', $output, $matches), $output);

        return $matches[1];
    }
}
