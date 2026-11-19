<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The help-screenshot account is a super-administrator made for local use.
 * It must not exist where APP_ENV is production, which is what both staging
 * and production use.
 */
class ScreenshotAccountRemovalTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'screenshots@help.test';

    public function test_in_production_the_account_is_removed_with_everything_that_hangs_off_it(): void
    {
        $account = $this->screenshotAccount();
        $account->createToken('left behind');
        $account->notify(new PhotoApprovedNotification($account->person));
        $this->app['env'] = 'production';

        $this->migration()->up();

        $this->assertDatabaseMissing('users', ['email' => self::EMAIL]);
        $this->assertSame(0, DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $account->id)->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $account->id)->count());
        $this->assertSame(0, DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $account->id)->count());
    }

    public function test_in_production_the_sample_official_email_it_left_on_a_real_record_goes_too(): void
    {
        $account = $this->screenshotAccount();
        $sample = $this->emailOn($account->person_id, 'staff.member@audit.gov.gh');
        $genuine = $this->emailOn($account->person_id, 'ama.mensah@audit.gov.gh');
        $this->app['env'] = 'production';

        $this->migration()->up();

        $this->assertSoftDeleted('contacts', ['id' => $sample->id]);
        $this->assertNotSoftDeleted('contacts', ['id' => $genuine->id]);
    }

    public function test_in_production_nobody_else_is_touched(): void
    {
        $this->screenshotAccount();
        $bystander = User::factory()->create(['person_id' => InstitutionPerson::factory()->create()->person_id]);
        $bystander->assignRole('super-administrator');
        $sameAddressElsewhere = $this->emailOn($bystander->person_id, 'staff.member@audit.gov.gh');
        $this->app['env'] = 'production';

        $this->migration()->up();

        $this->assertNotNull($bystander->fresh());
        $this->assertTrue($bystander->fresh()->hasRole('super-administrator'));
        $this->assertNotSoftDeleted('contacts', ['id' => $sameAddressElsewhere->id]);
    }

    public function test_the_person_the_account_was_attached_to_keeps_their_record(): void
    {
        $account = $this->screenshotAccount();
        $this->app['env'] = 'production';

        $this->migration()->up();

        $this->assertDatabaseHas('people', ['id' => $account->person_id]);
        $this->assertDatabaseHas('institution_person', ['person_id' => $account->person_id]);
    }

    public function test_outside_production_the_account_is_left_for_the_screenshot_run(): void
    {
        $account = $this->screenshotAccount();
        $sample = $this->emailOn($account->person_id, 'staff.member@audit.gov.gh');

        $this->migration()->up();

        $this->assertNotNull($account->fresh());
        $this->assertTrue($account->fresh()->hasRole('super-administrator'));
        $this->assertNotSoftDeleted('contacts', ['id' => $sample->id]);
    }

    public function test_in_production_it_does_nothing_when_the_account_is_not_there(): void
    {
        $bystander = User::factory()->create();
        $this->app['env'] = 'production';

        $this->migration()->up();
        $this->migration()->up();

        $this->assertNotNull($bystander->fresh());
    }

    private function migration(): Migration
    {
        $files = glob(database_path('migrations/*_remove_help_screenshot_account_from_production.php'));
        $this->assertCount(1, $files);

        return require $files[0];
    }

    private function screenshotAccount(): User
    {
        $account = User::factory()->create([
            'email' => self::EMAIL,
            'person_id' => InstitutionPerson::factory()->create()->person_id,
        ]);
        $account->assignRole('super-administrator');

        return $account;
    }

    private function emailOn(int $personId, string $address): Contact
    {
        return Contact::create([
            'person_id' => $personId,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => $address,
        ]);
    }
}
