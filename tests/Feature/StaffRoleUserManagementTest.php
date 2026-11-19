<?php

namespace Tests\Feature;

use App\Models\InstitutionPerson;
use App\Models\User;
use Database\Seeders\AllPermissionsSeeder;
use Database\Seeders\UserPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Creating a user includes choosing its roles, so the staff role must not
 * hold create user, nor the two permissions that were handed out with it.
 */
class StaffRoleUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private const USER_MANAGEMENT = ['view user', 'create user', 'update user'];

    public function test_the_user_permissions_seeder_gives_the_staff_role_no_user_management(): void
    {
        $this->seed(UserPermissionsSeeder::class);

        $this->assertSame([], $this->userManagementHeldByStaff());
    }

    public function test_the_documented_deploy_seeders_leave_the_staff_role_as_the_role_map_defines_it(): void
    {
        $before = $this->staffPermissions();

        $this->seed(UserPermissionsSeeder::class);
        $this->seed(AllPermissionsSeeder::class);

        $this->assertSame($before, $this->staffPermissions());
    }

    public function test_the_user_permissions_seeder_still_gives_them_to_the_super_administrator(): void
    {
        Role::findByName('super-administrator')->revokePermissionTo(self::USER_MANAGEMENT);

        $this->seed(UserPermissionsSeeder::class);

        $held = Role::findByName('super-administrator')->permissions->pluck('name');
        foreach (self::USER_MANAGEMENT as $permission) {
            $this->assertContains($permission, $held);
        }
    }

    public function test_a_member_of_staff_cannot_create_a_user(): void
    {
        Mail::fake();

        $this->actingAs($this->staffUser())
            ->post(route('user.store'), $this->newUserPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new.user@audit.gov.gh']);
        Mail::assertNothingSent();
    }

    public function test_a_member_of_staff_cannot_open_another_users_account(): void
    {
        $this->actingAs($this->staffUser())
            ->get(route('user.show', ['user' => User::factory()->create()->id]))
            ->assertForbidden();
    }

    public function test_the_migration_takes_user_management_away_from_the_staff_role(): void
    {
        Mail::fake();
        $staffRole = Role::findByName('staff');
        $staffRole->givePermissionTo(self::USER_MANAGEMENT);
        $otherStaffPermissions = array_values(array_diff($this->staffPermissions(), self::USER_MANAGEMENT));
        $user = $this->staffUser();
        $this->assertTrue($user->can('create user'));

        $this->migration()->up();

        $this->assertSame([], $this->userManagementHeldByStaff());
        $this->assertSame($otherStaffPermissions, $this->staffPermissions());

        $this->actingAs($user->fresh())
            ->post(route('user.store'), $this->newUserPayload())
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'new.user@audit.gov.gh']);
    }

    public function test_the_migration_leaves_every_other_role_alone(): void
    {
        Role::findByName('staff')->givePermissionTo(self::USER_MANAGEMENT);
        Role::findByName('admin-user')->givePermissionTo('view user');
        $before = Role::query()
            ->where('name', '!=', 'staff')
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->sort()->values()->all()])
            ->all();

        $this->migration()->up();

        $after = Role::query()
            ->where('name', '!=', 'staff')
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->sort()->values()->all()])
            ->all();

        $this->assertSame($before, $after);
        $this->assertContains('create user', $after['super-administrator']);
        $this->assertContains('view user', $after['admin-user']);
    }

    public function test_the_migration_does_nothing_where_there_is_nothing_to_remove(): void
    {
        $before = $this->staffPermissions();

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame($before, $this->staffPermissions());
    }

    public function test_the_migration_runs_on_a_database_without_the_role_or_the_permissions(): void
    {
        Permission::query()->whereIn('name', self::USER_MANAGEMENT)->delete();
        $this->migration()->up();

        Role::findByName('staff')->delete();
        $this->migration()->up();

        $this->assertNull(Role::query()->where('name', 'staff')->first());
    }

    private function migration(): Migration
    {
        $files = glob(database_path('migrations/*_revoke_user_management_from_staff_role.php'));
        $this->assertCount(1, $files);

        return require $files[0];
    }

    /**
     * @return array<int, string>
     */
    private function staffPermissions(): array
    {
        return Role::findByName('staff')->permissions()->pluck('name')->sort()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function userManagementHeldByStaff(): array
    {
        return array_values(array_intersect($this->staffPermissions(), self::USER_MANAGEMENT));
    }

    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => InstitutionPerson::factory()->create()->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function newUserPayload(): array
    {
        return [
            'userData' => [
                'bio' => ['name' => 'New User', 'email' => 'new.user@audit.gov.gh'],
                'roles' => [],
            ],
        ];
    }
}
