<?php

namespace Tests\Feature\Reports;

use App\Enums\ContactTypeEnum;
use App\Enums\EmployeeStatusEnum;
use App\Enums\Identity;
use App\Models\Contact;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every spreadsheet export of staff data needs the download permission for
 * the kind of data it contains; being signed in is not enough.
 */
class ExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const ACTIVE = 'download active staff data';

    private const SEPARATED = 'download separated staff data';

    private const RECRUITMENT = 'download recruitment data';

    private const PROMOTION = 'download promotion data';

    private const RANK = 'download rank staff data';

    private const ALL_DOWNLOAD_PERMISSIONS = [
        self::ACTIVE,
        self::SEPARATED,
        self::RECRUITMENT,
        self::PROMOTION,
        self::RANK,
    ];

    private Institution $institution;

    private Unit $unit;

    private Job $rank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->unit = Unit::factory()->create(['institution_id' => $this->institution->id]);
        $this->rank = Job::factory()->create([
            'name' => 'Auditor',
            'institution_id' => $this->institution->id,
            'job_category_id' => JobCategory::factory()->create(['institution_id' => $this->institution->id])->id,
        ]);
    }

    /**
     * Route name, the permission that guards it, and the file it downloads.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function exports(): array
    {
        return [
            'staff position' => ['report.staff', self::ACTIVE, 'staff-position.xlsx'],
            'staff details' => ['report.staff-details', self::ACTIVE, 'staff-details.xlsx'],
            'staff to retire' => ['report.staff-retirement', self::ACTIVE, 'staff-to-retire.xlsx'],
            'pending transfers' => ['report.staff-pending-transfer', self::ACTIVE, 'staff-pending-transfer.xlsx'],
            'staff positions' => ['report.staff-positions', self::ACTIVE, 'positions.xls'],
            'staff list' => ['staff-list', self::ACTIVE, 'staff-list.xlsx'],

            'retired staff' => ['report.retirements', self::SEPARATED, 'retired-staff.xlsx'],
            'all separated staff' => ['report.retirements-all', self::SEPARATED, 'all-retired-staff.xlsx'],
            'deceased staff' => ['report.retirements-deceased', self::SEPARATED, 'deceased-staff.xlsx'],
            'terminated staff' => ['report.retirements-terminated', self::SEPARATED, 'terminated-staff.xlsx'],
            'resigned staff' => ['report.resignation', self::SEPARATED, 'resignation-staff.xlsx'],
            'suspended staff' => ['report.suspended', self::SEPARATED, 'suspended.xls'],
            'voluntary retirement' => ['report.vol-retirement', self::SEPARATED, 'voluntary-retirement.xls'],
            'dismissed staff' => ['report.dismissed', self::SEPARATED, 'dismissed.xls'],
            'vacation of post' => ['report.vacation-of-post', self::SEPARATED, 'vacation-of-post.xls'],
            'leave with pay' => ['report.leave-pay', self::SEPARATED, 'leave-with-pay.xlsx'],
            'leave without pay' => ['report.leave-without-pay', self::SEPARATED, 'leave-without-pay.xlsx'],

            'recruitment data' => ['report.recruitment.export-data', self::RECRUITMENT, 'all_recruitment.xlsx'],
            'recruitment summary' => ['report.recruitment.export-summary', self::RECRUITMENT, 'recruitment_summary.xlsx'],

            'promotion list' => ['export.promotion', self::PROMOTION, 'Promotion List.xlsx'],
            'promotion summary' => ['export.promotion-list', self::PROMOTION, 'promotion summary.xlsx'],

            'staff of a rank' => ['rank-staff.export-rank', self::RANK, 'Auditor staff.xlsx'],
            'all-time staff of a rank' => ['rank-staff.export-rank-all', self::RANK, 'Auditors all time list.xlsx'],
        ];
    }

    #[DataProvider('exports')]
    public function test_staff_role_is_forbidden_from_the_export(string $route): void
    {
        $this->actingAs($this->staffUser())
            ->get($this->urlFor($route))
            ->assertForbidden();
    }

    #[DataProvider('exports')]
    public function test_user_with_the_permission_downloads_the_export(string $route, string $permission, string $filename): void
    {
        $this->createStaffToExport();

        $this->actingAs($this->userWithPermissions([$permission]))
            ->get($this->urlFor($route))
            ->assertOk()
            ->assertDownload($filename);
    }

    #[DataProvider('exports')]
    public function test_the_permission_for_a_different_export_is_not_enough(string $route, string $permission): void
    {
        $otherPermissions = array_values(array_diff(self::ALL_DOWNLOAD_PERMISSIONS, [$permission]));

        $this->actingAs($this->userWithPermissions($otherPermissions))
            ->get($this->urlFor($route))
            ->assertForbidden();
    }

    #[DataProvider('exports')]
    public function test_guest_is_sent_to_login_instead_of_the_export(string $route): void
    {
        $this->get($this->urlFor($route))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------------
    // Staff position export: it used to ask for view staff position in the
    // controller, which no holder of the download permission had
    // -----------------------------------------------------------------------

    public function test_staff_role_is_forbidden_from_the_staff_position_export(): void
    {
        $this->actingAs($this->staffUser())
            ->get(route('report.staff'))
            ->assertForbidden();
    }

    public function test_view_staff_position_alone_no_longer_downloads_the_staff_position_export(): void
    {
        $this->actingAs($this->userWithPermissions(['view staff position']))
            ->get(route('report.staff'))
            ->assertForbidden();
    }

    public function test_the_download_permission_alone_downloads_the_staff_position_export(): void
    {
        $this->createStaffToExport();
        $user = $this->userWithPermissions([self::ACTIVE]);

        $this->assertFalse($user->can('view staff position'));

        $this->actingAs($user)
            ->get(route('report.staff'))
            ->assertOk()
            ->assertDownload('staff-position.xlsx');
    }

    public function test_staff_position_export_still_records_the_download(): void
    {
        $user = $this->userWithPermissions([self::ACTIVE]);

        $this->actingAs($user)
            ->get(route('report.staff', ['gender' => 'F']))
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'downloaded staff position',
            'event' => 'download',
            'causer_id' => $user->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // The hr-user role
    // -----------------------------------------------------------------------

    public function test_hr_user_downloads_the_active_and_separated_staff_exports(): void
    {
        $this->createStaffToExport();
        $hr = User::factory()->create();
        $hr->assignRole('hr-user');

        $exports = [
            'report.staff' => 'staff-position.xlsx',
            'report.staff-details' => 'staff-details.xlsx',
            'staff-list' => 'staff-list.xlsx',
            'report.retirements-all' => 'all-retired-staff.xlsx',
        ];

        foreach ($exports as $route => $filename) {
            $this->actingAs($hr)
                ->get(route($route))
                ->assertOk()
                ->assertDownload($filename);
        }
    }

    public function test_hr_user_is_refused_the_recruitment_promotion_and_rank_exports(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole('hr-user');

        foreach (['report.recruitment.export-data', 'export.promotion-list', 'rank-staff.export-rank'] as $route) {
            $this->actingAs($hr)
                ->get($this->urlFor($route))
                ->assertForbidden();
        }
    }

    // -----------------------------------------------------------------------
    // Other gates on the same routes
    // -----------------------------------------------------------------------

    public function test_staff_role_is_forbidden_from_the_past_promotion_export_route(): void
    {
        $this->actingAs($this->staffUser())
            ->get(route('promotion.export', ['promotion' => 2024]))
            ->assertForbidden();
    }

    public function test_staff_list_export_requires_the_password_to_have_been_changed(): void
    {
        $user = $this->userWithPermissions([self::ACTIVE]);
        $user->forceFill(['password_change_at' => null])->save();

        $this->actingAs($user)
            ->get(route('staff-list'))
            ->assertRedirect(route('change-password.index'));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function urlFor(string $route): string
    {
        return match ($route) {
            'rank-staff.export-rank', 'rank-staff.export-rank-all', 'export.promotion' => route($route, ['rank' => $this->rank->id]),
            default => route($route),
        };
    }

    /**
     * A signed-in member of staff holding only the staff role.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => $this->staffWithStatus(EmployeeStatusEnum::Active)->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * One member of staff in every employment status, each with a rank and a
     * unit, so that every export has a row to write.
     */
    private function createStaffToExport(): void
    {
        foreach (EmployeeStatusEnum::cases() as $status) {
            $staff = $this->staffWithStatus($status);
            $staff->units()->attach($this->unit->id, ['start_date' => now()->subYears(4)]);
            $staff->ranks()->attach($this->rank->id, ['start_date' => now()->subYears(4)]);
        }
    }

    private function staffWithStatus(EmployeeStatusEnum $status): InstitutionPerson
    {
        $person = Person::factory()->create(['date_of_birth' => now()->subYears(58)->toDateString()]);
        $person->identities()->create(['id_type' => Identity::GhanaCard, 'id_number' => 'GHA-' . fake()->unique()->numerify('#########') . '-1']);
        Contact::create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => fake()->numerify('024#######'),
        ]);

        $staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'institution_id' => $this->institution->id,
        ]);
        $staff->statuses()->create([
            'status' => $status,
            'start_date' => now()->subYears(5),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }
}
