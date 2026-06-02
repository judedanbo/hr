<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PerPageHelperTest extends TestCase
{
    use RefreshDatabase;

    private function withRequest(string $uri): void
    {
        $this->app->instance('request', Request::create($uri));
    }

    public function test_defaults_to_the_configured_pagination_size(): void
    {
        $this->withRequest('/');
        $this->assertSame(10, per_page());
    }

    public function test_reflects_a_changed_setting(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->pagination_size = 30;
        $settings->save();

        $this->withRequest('/');
        $this->assertSame(30, per_page());
    }

    public function test_uses_a_valid_per_page_override(): void
    {
        $this->withRequest('/?per_page=25');
        $this->assertSame(25, per_page());
    }

    public function test_clamps_high_override(): void
    {
        $this->withRequest('/?per_page=1000');
        $this->assertSame(100, per_page());
    }

    public function test_clamps_low_override(): void
    {
        $this->withRequest('/?per_page=2');
        $this->assertSame(5, per_page());
    }

    public function test_ignores_non_numeric_override(): void
    {
        $this->withRequest('/?per_page=abc');
        $this->assertSame(10, per_page());
    }

    public function test_clamps_negative_override_to_minimum(): void
    {
        $this->withRequest('/?per_page=-50');
        $this->assertSame(5, per_page());
    }

    public function test_truncates_then_clamps_float_override(): void
    {
        $this->withRequest('/?per_page=10.5');
        $this->assertSame(10, per_page());
    }
}
