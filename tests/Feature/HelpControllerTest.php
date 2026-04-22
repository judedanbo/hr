<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): User
    {
        $user = User::factory()->create(['password_change_at' => now()]);
        $user->assignRole('staff');

        return $user;
    }

    public function test_help_page_requires_auth(): void
    {
        $this->get('/help')->assertRedirect('/login');
    }

    public function test_help_page_rewrites_screenshot_paths(): void
    {
        $response = $this->actingAs($this->authedUser())->get('/help');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Help/Index')
                ->where('content', fn (string $content) => str_contains($content, '/help/screenshots/login-page.png')
                    && ! str_contains($content, 'src="screenshots/')
                )
            );
    }

    public function test_screenshot_endpoint_serves_png(): void
    {
        $response = $this->actingAs($this->authedUser())
            ->get('/help/screenshots/login-page.png');

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));
    }

    public function test_screenshot_endpoint_rejects_missing_file(): void
    {
        $this->actingAs($this->authedUser())
            ->get('/help/screenshots/does-not-exist.png')
            ->assertNotFound();
    }

    public function test_screenshot_endpoint_rejects_traversal(): void
    {
        // Route constraint restricts filenames to [A-Za-z0-9._-]+\.png,
        // so traversal attempts fail route matching and return 404.
        $this->actingAs($this->authedUser())
            ->get('/help/screenshots/..%2F..%2F.env')
            ->assertNotFound();
    }
}
