<?php

namespace Tests\Feature\Qualification;

use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreQualificationWithDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
    }

    public function test_creates_qualification_without_document(): void
    {
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'institution' => 'University of Ghana',
                'year' => '2015',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('qualifications', 1);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_creates_qualification_with_attached_document(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'institution' => 'University of Ghana',
                'year' => '2015',
                'document_type' => 'A', // AcademicCertificate
                'document_title' => 'Degree certificate',
                'file_name' => UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionDoesntHaveErrors();

        $qualification = Qualification::first();
        $this->assertNotNull($qualification);
        $this->assertCount(1, $qualification->documents);

        $doc = $qualification->documents->first();
        $this->assertSame('Degree certificate', $doc->document_title);
        $this->assertSame('A', $doc->document_type);
        $this->assertNotEmpty($doc->file_name);
        $this->assertSame('P', $doc->document_status);
    }

    public function test_document_fields_without_file_are_ignored(): void
    {
        [$user, $person] = $this->staffUser();

        // Sending document_type and document_title without a file — should be ignored,
        // not cause validation errors, and not create a document row.
        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'document_type' => 'A',
                'document_title' => 'Degree certificate',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('qualifications', 1);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_file_without_document_type_is_rejected(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'file_name' => UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['document_type']);

        $this->assertDatabaseCount('qualifications', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'document_type' => 'A',
                'file_name' => UploadedFile::fake()->create('malware.exe', 200, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors(['file_name']);

        $this->assertDatabaseCount('qualifications', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_document_title_auto_filled_from_filename_when_blank(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'document_type' => 'A',
                // No document_title — should be derived from filename
                'file_name' => UploadedFile::fake()->create('my-degree-cert.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionDoesntHaveErrors();

        $doc = Qualification::first()->documents->first();
        $this->assertSame('my-degree-cert', $doc->document_title);
    }

    /**
     * @return array{User, Person}
     */
    private function staffUser(): array
    {
        $person = Person::factory()->create();
        $user = User::factory()->create([
            'person_id' => $person->id,
            'password_change_at' => now(),
        ]);
        $user->assignRole('staff');

        return [$user, $person];
    }
}
