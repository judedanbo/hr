<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\JobCategory;
use App\Models\Person;
use PHPUnit\Framework\TestCase;

class InstitutionPersonFactoryTest extends TestCase
{
    public function test_institution_person_factory_definition_structure(): void
    {
        $factory = new \Database\Factories\InstitutionPersonFactory();
        $definition = $factory->definition();

        // Test that all required fields are present in the definition
        $expectedFields = [
            'institution_id',
            'person_id',
            'file_number',
            'staff_number',
            'old_staff_number',
            'hire_date',
            'end_date',
            // 'job_category_id'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $definition, "Factory should define {$field}");
        }
    }

    public function test_institution_person_factory_has_required_methods(): void
    {
        $factory = new \Database\Factories\InstitutionPersonFactory();

        // Test that factory has useful state methods
        $this->assertTrue(method_exists($factory, 'separated'));
        $this->assertTrue(method_exists($factory, 'withStaffNumber'));
        $this->assertTrue(method_exists($factory, 'withFileNumber'));
        $this->assertTrue(method_exists($factory, 'recentHire'));
        $this->assertTrue(method_exists($factory, 'longTerm'));
        $this->assertTrue(method_exists($factory, 'withoutOldStaffNumber'));
    }

    public function test_institution_person_factory_states_work(): void
    {
        $factory = new \Database\Factories\InstitutionPersonFactory();

        // Test separated state
        $separatedState = $factory->separated();
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $separatedState);

        // Test withStaffNumber state
        $staffNumberState = $factory->withStaffNumber('TEST001');
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $staffNumberState);

        // Test withFileNumber state
        $fileNumberState = $factory->withFileNumber('FILE001');
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $fileNumberState);

        // Test recentHire state
        $recentHireState = $factory->recentHire();
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $recentHireState);

        // Test longTerm state
        $longTermState = $factory->longTerm();
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $longTermState);

        // Test withoutOldStaffNumber state
        $noOldStaffState = $factory->withoutOldStaffNumber();
        $this->assertInstanceOf(\Database\Factories\InstitutionPersonFactory::class, $noOldStaffState);
    }

    public function test_institution_person_factory_generates_valid_data(): void
    {
        $factory = new \Database\Factories\InstitutionPersonFactory();
        $definition = $factory->definition();

        // Test that staff_number follows expected pattern
        $this->assertIsString($definition['staff_number']);
        $this->assertStringStartsWith('STAFF', $definition['staff_number']);

        // Test that file_number follows expected pattern
        $this->assertIsString($definition['file_number']);
        $this->assertStringStartsWith('FILE', $definition['file_number']);

        // // Test that email is valid format
        // $this->assertIsString($definition['email']);
        // $this->assertStringContainsString('@', $definition['email']);

        // Test that end_date defaults to null (active staff)
        $this->assertNull($definition['end_date']);

        // Test that hire_date is in the past
        $this->assertInstanceOf(\DateTime::class, $definition['hire_date']);
        $this->assertLessThan(now(), $definition['hire_date']);
    }

    public function test_related_factories_exist(): void
    {
        // Test that required factories exist
        $this->assertTrue(class_exists(\Database\Factories\InstitutionFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\PersonFactory::class));
        $this->assertTrue(class_exists(\Database\Factories\JobCategoryFactory::class));
    }

    public function test_related_models_exist(): void
    {
        // Test that required models exist
        $this->assertTrue(class_exists(Institution::class));
        $this->assertTrue(class_exists(Person::class));
        $this->assertTrue(class_exists(InstitutionPerson::class));
        $this->assertTrue(class_exists(JobCategory::class));
    }

    public function test_institution_person_model_has_factory(): void
    {
        // Test that InstitutionPerson model has the HasFactory trait
        $model = new InstitutionPerson();
        $traits = class_uses_recursive(InstitutionPerson::class);

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }

    public function test_institution_factory_definition_is_valid(): void
    {
        $factory = new \Database\Factories\InstitutionFactory();
        $definition = $factory->definition();

        $expectedFields = ['name', 'abbreviation', 'start_date', 'end_date', 'status'];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $definition, "Institution factory should define {$field}");
        }

        $this->assertEquals('active', $definition['status']);
        $this->assertNull($definition['end_date']);
    }

    public function test_job_category_factory_definition_is_valid(): void
    {
        $factory = new \Database\Factories\JobCategoryFactory();
        $definition = $factory->definition();

        $expectedFields = [
            'name',
            'short_name',
            'level',
            // 'job_category_id',
            'description',
            'institution_id',
            'start_date',
            'end_date'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $definition, "JobCategory factory should define {$field}");
        }

        $this->assertIsInt($definition['level']);
        $this->assertGreaterThanOrEqual(1, $definition['level']);
        $this->assertLessThanOrEqual(10, $definition['level']);
    }
}
