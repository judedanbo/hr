<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\HelpScreenshotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HelpScreenshotSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'screenshots@help.test';

    public function test_it_refuses_to_run_in_production(): void
    {
        $staff = InstitutionPerson::factory()->create();
        $this->app['env'] = 'production';

        $output = $this->runSeeder();

        $this->assertStringContainsString('does not run in production', $output);
        $this->assertFalse(User::query()->where('email', self::EMAIL)->exists());
        $this->assertSame(0, Qualification::query()->where('person_id', $staff->person_id)->count());
        $this->assertSame(0, Contact::query()->where('person_id', $staff->person_id)->count());
    }

    public function test_it_still_prepares_the_screenshot_account_outside_production(): void
    {
        $staff = InstitutionPerson::factory()->create();

        $this->runSeeder();

        $user = User::query()->where('email', self::EMAIL)->sole();
        $this->assertTrue($user->hasRole('super-administrator'));
        $this->assertSame($staff->person_id, $user->person_id);
        $this->assertSame(3, Qualification::query()->where('person_id', $staff->person_id)->count());
    }

    public function test_the_seeder_holds_no_password_of_its_own(): void
    {
        $source = file_get_contents(database_path('seeders/HelpScreenshotSeeder.php'));

        $this->assertDoesNotMatchRegularExpression('/(bcrypt|Hash::make)\(\s*[\'"]/', $source);
        $this->assertDoesNotMatchRegularExpression('/[\'"]password[\'"]\s*=>\s*[\'"]/', $source);
    }

    private function runSeeder(): string
    {
        Artisan::call('db:seed', ['--class' => HelpScreenshotSeeder::class, '--force' => true]);

        return Artisan::output();
    }
}
