<?php

namespace Tests\Feature;

use Database\Seeders\AllPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The sidebar gated its Audit Logs link on "view all audit logs", a permission
 * that was never seeded and appeared nowhere else in the codebase, so the link
 * rendered for nobody. Nothing caught it because the gate simply evaluated
 * false. This asserts every permission the Vue tree gates on actually exists.
 */
class NavigationPermissionsExistTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_permission_gated_in_the_vue_tree_is_seeded(): void
    {
        $this->seed(AllPermissionsSeeder::class);

        $seeded = Permission::pluck('name')->all();

        $missing = [];

        foreach ($this->gatedPermissionNames() as $name => $files) {
            if (! in_array($name, $seeded, true)) {
                $missing[$name] = $files;
            }
        }

        $this->assertSame([], $missing, $this->describeMissing($missing));
    }

    /**
     * Scan the Vue tree for permission strings passed to a `permissions`-scoped
     * `includes(...)` call. Matching on the receiver matters: a bare
     * `.includes("approved")` is a status check, not a permission.
     *
     * @return array<string, list<string>>
     */
    protected function gatedPermissionNames(): array
    {
        $found = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('js'))
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }

            preg_match_all(
                '/permissions[A-Za-z0-9_.?\[\]\'"]*\s*\.includes\(\s*["\']([^"\']+)["\']\s*\)/',
                (string) file_get_contents($file->getPathname()),
                $matches
            );

            foreach ($matches[1] as $permission) {
                $found[$permission][] = str_replace(base_path() . '/', '', $file->getPathname());
            }
        }

        return $found;
    }

    /**
     * @param  array<string, list<string>>  $missing
     */
    protected function describeMissing(array $missing): string
    {
        if ($missing === []) {
            return '';
        }

        $lines = ['Permissions gated in Vue but absent from AllPermissionsSeeder:'];

        foreach ($missing as $name => $files) {
            $lines[] = sprintf('  "%s" — %s', $name, implode(', ', array_unique($files)));
        }

        return implode(PHP_EOL, $lines);
    }
}
