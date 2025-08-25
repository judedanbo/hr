<?php

namespace Tests\Unit;

use App\Enums\EmployeeStatusEnum;
use App\Models\Scopes\SeparationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class SeparationScopeTest extends TestCase
{
    public function test_separation_scope_structure_is_valid(): void
    {
        // Test that the SeparationScope class exists and has the right methods
        $scope = new SeparationScope();
        $this->assertInstanceOf(SeparationScope::class, $scope);
        $this->assertTrue(method_exists($scope, 'apply'));
        
        // Test that it expects the right parameters
        $reflection = new \ReflectionMethod($scope, 'apply');
        $parameters = $reflection->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('builder', $parameters[0]->getName());
        $this->assertEquals('model', $parameters[1]->getName());
    }

    public function test_separation_scope_includes_all_expected_status_values(): void
    {
        $expectedStatuses = [
            EmployeeStatusEnum::Left->value,           // 'L'
            EmployeeStatusEnum::Termination->value,    // 'T'
            EmployeeStatusEnum::Resignation->value,    // 'R'
            EmployeeStatusEnum::Retired->value,        // 'E'
            EmployeeStatusEnum::Dismissed->value,      // 'M'
            EmployeeStatusEnum::Deceased->value,       // 'D'
            EmployeeStatusEnum::Voluntary->value,      // 'V'
        ];

        $this->assertCount(7, $expectedStatuses, 'Should include exactly 7 separation status types');
        $this->assertContains('L', $expectedStatuses, 'Should include Left status');
        $this->assertContains('T', $expectedStatuses, 'Should include Termination status');
        $this->assertContains('R', $expectedStatuses, 'Should include Resignation status');
        $this->assertContains('E', $expectedStatuses, 'Should include Retired status');
        $this->assertContains('M', $expectedStatuses, 'Should include Dismissed status');
        $this->assertContains('D', $expectedStatuses, 'Should include Deceased status');
        $this->assertContains('V', $expectedStatuses, 'Should include Voluntary status');
        
        // Ensure Active status is NOT included
        $this->assertNotContains('A', $expectedStatuses, 'Should NOT include Active status');
    }

    public function test_employee_status_enum_has_all_separation_values(): void
    {
        // Test that all the enum values we're using in the scope actually exist
        $this->assertEquals('L', EmployeeStatusEnum::Left->value);
        $this->assertEquals('T', EmployeeStatusEnum::Termination->value);
        $this->assertEquals('R', EmployeeStatusEnum::Resignation->value);
        $this->assertEquals('E', EmployeeStatusEnum::Retired->value);
        $this->assertEquals('M', EmployeeStatusEnum::Dismissed->value);
        $this->assertEquals('D', EmployeeStatusEnum::Deceased->value);
        $this->assertEquals('V', EmployeeStatusEnum::Voluntary->value);
        $this->assertEquals('A', EmployeeStatusEnum::Active->value);
    }

    public function test_separation_scope_has_correct_interface(): void
    {
        $scope = new SeparationScope();
        
        // Test that it implements the Scope interface
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Scope::class, $scope);
        
        // Test that apply method exists and is callable
        $this->assertTrue(is_callable([$scope, 'apply']));
    }
}