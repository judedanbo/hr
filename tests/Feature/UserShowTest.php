<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    /** A viewer that can view users and their roles/permissions. */
    protected function viewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view user', 'view user roles', 'view user permissions']);

        return $user;
    }

    public function test_show_splits_direct_and_inherited_permissions(): void
    {
        $direct = Permission::firstOrCreate(['name' => 'directly.assigned']);
        $viaRole = Permission::firstOrCreate(['name' => 'role.granted']);

        $role = Role::firstOrCreate(['name' => 'editor']);
        $role->givePermissionTo($viaRole);

        $target = User::factory()->create();
        $target->assignRole($role);
        $target->givePermissionTo($direct);

        $response = $this->actingAs($this->viewer())
            ->get(route('user.show', ['user' => $target->id]));

        $response->assertOk();
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('User/Show')
                ->where('user.email', $target->email)
                ->where('user.direct_permissions.0.name', 'directly.assigned')
                ->where('user.inherited_permissions.0.name', 'role.granted')
                ->where('user.inherited_permissions.0.via', 'editor')
        );
    }

    public function test_directly_held_permission_is_not_listed_as_inherited(): void
    {
        $shared = Permission::firstOrCreate(['name' => 'shared.permission']);

        $role = Role::firstOrCreate(['name' => 'editor']);
        $role->givePermissionTo($shared);

        $target = User::factory()->create();
        $target->assignRole($role);
        $target->givePermissionTo($shared); // also held directly

        $response = $this->actingAs($this->viewer())
            ->get(route('user.show', ['user' => $target->id]));

        $response->assertInertia(function (Assert $page) {
            $direct = collect($page->toArray()['props']['user']['direct_permissions'])->pluck('name');
            $inherited = collect($page->toArray()['props']['user']['inherited_permissions'])->pluck('name');

            $this->assertTrue($direct->contains('shared.permission'));
            $this->assertFalse($inherited->contains('shared.permission'));
        });
    }

    public function test_inherited_permission_via_lists_all_granting_roles(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'multi.role.granted']);

        $roleA = Role::firstOrCreate(['name' => 'editor']);
        $roleB = Role::firstOrCreate(['name' => 'publisher']);
        $roleA->givePermissionTo($permission);
        $roleB->givePermissionTo($permission);

        $target = User::factory()->create();
        $target->assignRole([$roleA->name, $roleB->name]);

        $response = $this->actingAs($this->viewer())
            ->get(route('user.show', ['user' => $target->id]));

        $response->assertInertia(function (Assert $page) {
            $inherited = collect($page->toArray()['props']['user']['inherited_permissions'])
                ->firstWhere('name', 'multi.role.granted');

            $this->assertNotNull($inherited);
            $this->assertStringContainsString('editor', $inherited['via']);
            $this->assertStringContainsString('publisher', $inherited['via']);
        });
    }
}
