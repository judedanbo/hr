<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The sidebar decides what to show from auth.permissions, the permissions a
 * user actually holds. A link gated on a name that no seeder creates is
 * therefore hidden from everyone, a super-administrator included: the
 * Gate::before override that lets them through on the server never reaches
 * the front end.
 */
class SidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_permission_the_sidebar_checks_is_seeded(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/NewAuthenticated.vue'));

        preg_match_all('/permissions\.value\?\.includes\(\s*([\'"])(.+?)\1\s*\)/', $layout, $matches);
        $checked = array_values(array_unique($matches[2]));

        $this->assertNotEmpty($checked, 'Found no permission checks in the sidebar. Has the layout changed shape?');
        $this->assertSame(
            [],
            array_values(array_diff($checked, Permission::query()->pluck('name')->all())),
            'The sidebar checks permissions that no seeder creates, so the links they gate never appear.',
        );
    }
}
