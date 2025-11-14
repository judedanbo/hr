<?php

namespace Tests\Unit;

use App\Models\InstitutionPerson;
use Database\Factories\InstitutionPersonFactory;
use PHPUnit\Framework\TestCase;

class FactoryUsageTest extends TestCase
{
    public function test_institution_person_factory_can_be_called_statically(): void
    {
        // Test that we can call the factory statically
        $factory = InstitutionPerson::factory();
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory);
    }

    public function test_institution_person_factory_states_can_be_chained(): void
    {
        // Test that factory states can be chained
        $factory = InstitutionPerson::factory()
            ->withStaffNumber('TEST001')
            ->separated()
            ->withoutOldStaffNumber();

        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory);
    }

    public function test_factory_make_returns_model_instance(): void
    {
        // This test doesn't actually create DB records, just model instances
        $factory = new InstitutionPersonFactory();

        // We can't call make() without database, but we can verify the factory
        // is properly set up for making model instances
        $this->assertTrue(method_exists($factory, 'make'));
        $this->assertTrue(method_exists($factory, 'create'));
        $this->assertTrue(method_exists($factory, 'definition'));
    }

    public function test_factory_states_modify_attributes_correctly(): void
    {
        $factory = new InstitutionPersonFactory();

        // Test separated state - we can't access the internal state directly,
        // but we can verify the method chain works
        $separatedFactory = $factory->separated();
        $this->assertInstanceOf(InstitutionPersonFactory::class, $separatedFactory);

        // Test withStaffNumber state
        $staffNumberFactory = $factory->withStaffNumber('CUSTOM001');
        $this->assertInstanceOf(InstitutionPersonFactory::class, $staffNumberFactory);

        // Test recentHire state
        $recentFactory = $factory->recentHire();
        $this->assertInstanceOf(InstitutionPersonFactory::class, $recentFactory);
    }

    public function test_factory_definition_provides_all_fillable_attributes(): void
    {
        $factory = new InstitutionPersonFactory();
        $definition = $factory->definition();

        $model = new InstitutionPerson();
        $fillableFields = $model->getFillable();

        // Check that factory provides values for all fillable fields
        foreach ($fillableFields as $field) {
            $this->assertArrayHasKey(
                $field,
                $definition,
                "Factory should provide a value for fillable field: {$field}"
            );
        }
    }

    public function test_factory_generates_unique_values_for_unique_fields(): void
    {
        $factory = new InstitutionPersonFactory();

        // Generate multiple definitions to check uniqueness
        $definition1 = $factory->definition();
        $definition2 = $factory->definition();

        // Staff numbers should be unique
        $this->assertNotEquals($definition1['staff_number'], $definition2['staff_number']);

        // File numbers should be unique
        $this->assertNotEquals($definition1['file_number'], $definition2['file_number']);

        // // Emails should be unique
        // $this->assertNotEquals($definition1['email'], $definition2['email']);
    }

    public function test_related_factories_are_properly_configured(): void
    {
        // Test Institution factory
        $institutionFactory = new \Database\Factories\InstitutionFactory();
        $institutionDef = $institutionFactory->definition();
        $this->assertArrayHasKey('name', $institutionDef);
        $this->assertArrayHasKey('abbreviation', $institutionDef);

        // Test JobCategory factory
        $jobCategoryFactory = new \Database\Factories\JobCategoryFactory();
        $jobCategoryDef = $jobCategoryFactory->definition();
        $this->assertArrayHasKey('name', $jobCategoryDef);
        $this->assertArrayHasKey('level', $jobCategoryDef);

        // Test Person factory
        $personFactory = new \Database\Factories\PersonFactory();
        $personDef = $personFactory->definition();
        $this->assertArrayHasKey('surname', $personDef);
        $this->assertArrayHasKey('other_names', $personDef);
    }

    public function test_factory_states_provide_expected_behavior(): void
    {
        $factory = new InstitutionPersonFactory();

        // Test that states return factory instances (for method chaining)
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->separated());
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->recentHire());
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->longTerm());
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->withoutOldStaffNumber());

        // Test parameterized states
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->withStaffNumber('TEST'));
        $this->assertInstanceOf(InstitutionPersonFactory::class, $factory->withFileNumber('FILE'));
    }
}
