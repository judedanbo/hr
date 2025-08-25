<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SeparationControllerSimpleTest extends TestCase
{
    use WithFaker;

    public function test_separation_index_redirects_without_permission(): void
    {
        // Create a user without the required permission
        $user = User::factory()->create();

        // Attempt to access the separation index
        $response = $this->actingAs($user)->get('/separation');

        // bypass redirect to password confirmation



        // Should redirect to dashboard with error message
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to view separated staff');
    }

    public function test_separation_index_requires_authentication(): void
    {
        // Attempt to access without authentication
        $response = $this->get('/separation');

        // Should redirect to login
        $response->assertRedirect('/login');
    }

    public function test_separation_show_requires_authentication(): void
    {
        // Attempt to access without authentication
        $response = $this->get('/separation/1');

        // Should redirect to login
        $response->assertRedirect('/login');
    }

    // TODO: Fix this test when show method is implemented
    // public function test_separation_show_redirects_without_permission(): void
    // {
    //     // Create a user without the required permission
    //     $user = User::factory()->create();

    //     // Attempt to access a separation show page
    //     $response = $this->actingAs($user)->get('/separation/1');

    //     // Should redirect to dashboard with error message
    //     $response->assertRedirect(route('dashboard'))
    //         ->assertSessionHas('error', 'You do not have permission to view separated staff');
    // }

    public function test_separation_routes_exist(): void
    {
        // Test that the routes are properly registered
        $routes = collect(\Route::getRoutes()->getRoutes());

        $separationIndexRoute = $routes->first(function ($route) {
            return $route->uri === 'separation' && in_array('GET', $route->methods);
        });

        $separationShowRoute = $routes->first(function ($route) {
            return $route->uri === 'separation/{staff}' && in_array('GET', $route->methods);
        });

        $this->assertNotNull($separationIndexRoute, 'Separation index route should exist');
        $this->assertNotNull($separationShowRoute, 'Separation show route should exist');

        $this->assertEquals('separation.index', $separationIndexRoute->getName());
        $this->assertEquals('separation.show', $separationShowRoute->getName());
    }

    public function test_separation_controller_methods_exist(): void
    {
        // Test that the controller has the required methods
        $controller = new \App\Http\Controllers\SeparationController();

        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'show'));
    }

    public function test_separation_model_exists_and_has_global_scope(): void
    {
        // Test that the Separation model exists
        $this->assertTrue(class_exists(\App\Models\Separation::class));

        // Test that the SeparationScope exists  
        $this->assertTrue(class_exists(\App\Models\Scopes\SeparationScope::class));

        // Test that the model uses the scope
        $model = new \App\Models\Separation();
        $reflection = new \ReflectionClass($model);

        // Check for the ScopedBy attribute
        $attributes = $reflection->getAttributes();
        $hasScopedBy = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Illuminate\Database\Eloquent\Attributes\ScopedBy') {
                $hasScopedBy = true;
                break;
            }
        }

        $this->assertTrue($hasScopedBy, 'Separation model should have ScopedBy attribute');
    }

    public function test_employee_status_enum_contains_separation_statuses(): void
    {
        // Test that all separation statuses exist in the enum
        $separationStatuses = [
            'L' => \App\Enums\EmployeeStatusEnum::Left,
            'T' => \App\Enums\EmployeeStatusEnum::Termination,
            'R' => \App\Enums\EmployeeStatusEnum::Resignation,
            'E' => \App\Enums\EmployeeStatusEnum::Retired,
            'M' => \App\Enums\EmployeeStatusEnum::Dismissed,
            'D' => \App\Enums\EmployeeStatusEnum::Deceased,
            'V' => \App\Enums\EmployeeStatusEnum::Voluntary,
        ];

        foreach ($separationStatuses as $value => $enum) {
            $this->assertEquals($value, $enum->value, "Status {$enum->name} should have value {$value}");
        }
    }
}
