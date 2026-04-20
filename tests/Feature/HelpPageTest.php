<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    public function test_help_page_returns_sections_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Help/Index')
                ->has('sections', 20)
                ->has('sections.0', function ($section) {
                    $section->has('slug')
                        ->has('title')
                        ->has('html');
                });
        });
    }

    public function test_help_sections_are_ordered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/help');

        $response->assertInertia(function ($page) {
            $page->has('sections', 20);

            $sections = $page->toArray()['props']['sections'];
            $this->assertEquals('getting-started', $sections[0]['slug']);
            $this->assertEquals('glossary', $sections[19]['slug']);
        });
    }
}
