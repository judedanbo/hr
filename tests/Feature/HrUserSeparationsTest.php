<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The hr-user role holds the separated staff exports, so it also holds the
 * two permissions that open the screen those exports are offered on.
 */
class HrUserSeparationsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
    }

    public function test_hr_user_opens_the_separations_screen_and_sees_separated_staff(): void
    {
        $retired = $this->separatedStaff(EmployeeStatusEnum::Retired);
        $this->activeStaff();

        $this->actingAs($this->userWithRole('hr-user'))
            ->get(route('separation.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Separation/Index')
                ->has('separated.data', 1)
                ->where('separated.data.0.staff_number', $retired->staff_number)
                ->where('separated.data.0.id', $retired->person_id));
    }

    public function test_hr_user_opens_the_page_of_a_separated_person_from_the_list(): void
    {
        $retired = $this->separatedStaff(EmployeeStatusEnum::Retired);

        $this->actingAs($this->userWithRole('hr-user'))
            ->get(route('person.show', ['person' => $retired->person_id]))
            ->assertOk();
    }

    public function test_hr_user_downloads_a_separated_staff_export(): void
    {
        $this->separatedStaff(EmployeeStatusEnum::Retired);

        $this->actingAs($this->userWithRole('hr-user'))
            ->get(route('report.retirements-all'))
            ->assertOk()
            ->assertDownload('all-retired-staff.xlsx');
    }

    #[DataProvider('rolesWithoutTheSeparationsScreen')]
    public function test_roles_that_did_not_have_the_separations_screen_still_do_not(string $role): void
    {
        $this->separatedStaff(EmployeeStatusEnum::Retired);

        $this->actingAs($this->userWithRole($role))
            ->get(route('separation.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rolesWithoutTheSeparationsScreen(): array
    {
        return [
            'staff' => ['staff'],
            'admin-user' => ['admin-user'],
            'aag-admin' => ['aag-admin'],
            'general-admin-user' => ['general-admin-user'],
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole($role);

        return $user;
    }

    private function activeStaff(): InstitutionPerson
    {
        return $this->staffWithStatus(EmployeeStatusEnum::Active);
    }

    private function separatedStaff(EmployeeStatusEnum $status): InstitutionPerson
    {
        return $this->staffWithStatus($status);
    }

    private function staffWithStatus(EmployeeStatusEnum $status): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => Person::factory()->create()->id,
        ]);

        Status::create([
            'staff_id' => $staff->id,
            'status' => $status,
            'start_date' => now()->subYear(),
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        return $staff;
    }
}
