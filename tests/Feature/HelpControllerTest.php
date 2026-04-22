<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_page_transforms_screenshot_image_tags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('help.index'));

        $response->assertStatus(200);

        $sections = $response->viewData('page')['props']['sections'];

        // Find a section that has a screenshot reference
        $sectionWithImage = collect($sections)->first(function ($section) {
            return str_contains($section['html'], 'data-light-src');
        });

        $this->assertNotNull($sectionWithImage, 'At least one section should have transformed image tags');

        // Verify the transformed img tag has correct attributes
        $html = $sectionWithImage['html'];
        $this->assertStringContainsString('data-light-src="/help-screenshots/light/', $html);
        $this->assertStringContainsString('data-dark-src="/help-screenshots/dark/', $html);
        $this->assertStringContainsString('src="/help-screenshots/light/', $html);
        $this->assertStringNotContainsString('/ data-light-src', $html);

        // Verify no un-transformed help-screenshots references remain (root-level path)
        $this->assertDoesNotMatchRegularExpression(
            '#src="/help-screenshots/[^ld][^a-z]*\.png"#',
            $html,
            'No root-level screenshot paths should remain'
        );
    }

    public function test_help_page_loads_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('help.index'));

        $response->assertStatus(200);

        $sections = $response->viewData('page')['props']['sections'];
        $this->assertNotEmpty($sections);
    }
}
