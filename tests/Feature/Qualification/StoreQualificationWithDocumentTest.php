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

    public function test_creates_qualification_with_single_attached_document(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'institution' => 'University of Ghana',
                'year' => '2015',
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => ['A'], // AcademicCertificate
                'document_title' => ['Certificate of Completion'],
            ])
            ->assertSessionDoesntHaveErrors();

        $qualification = Qualification::first();
        $this->assertNotNull($qualification);
        $this->assertCount(1, $qualification->documents);

        $doc = $qualification->documents->first();
        $this->assertSame('Certificate of Completion', $doc->document_title);
        $this->assertSame('A', $doc->document_type);
        $this->assertNotEmpty($doc->file_name);
        $this->assertSame('P', $doc->document_status);
    }

    public function test_creates_qualification_with_multiple_attached_documents(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'institution' => 'University of Ghana',
                'year' => '2015',
                'file_name' => [
                    UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf'),
                    UploadedFile::fake()->create('transcript.pdf', 150, 'application/pdf'),
                    UploadedFile::fake()->create('certificate.jpg', 100, 'image/jpeg'),
                ],
                'document_type' => ['A', 'T', 'P'], // AcademicCertificate, Transcript, ProfessionalCertificate
                'document_title' => ['Degree Certificate', 'Official Transcript', 'Professional Certificate'],
            ])
            ->assertSessionDoesntHaveErrors();

        $qualification = Qualification::first();
        $this->assertNotNull($qualification);
        $this->assertCount(3, $qualification->documents);

        $qualification->documents->each(function ($doc) {
            $this->assertSame('P', $doc->document_status);
            $this->assertNotEmpty($doc->file_name);
        });

        $types = $qualification->documents->pluck('document_type')->toArray();
        $this->assertContains('A', $types);
        $this->assertContains('T', $types);
        $this->assertContains('P', $types);

        $titles = $qualification->documents->pluck('document_title')->toArray();
        $this->assertContains('Degree Certificate', $titles);
        $this->assertContains('Official Transcript', $titles);
        $this->assertContains('Professional Certificate', $titles);
    }

    public function test_document_fields_without_file_are_ignored(): void
    {
        [$user, $person] = $this->staffUser();

        // Sending document_type / document_title arrays without any file —
        // should be ignored, not cause validation errors, and not create a document row.
        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'document_type' => [],
                'document_title' => [],
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
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                // no document_type supplied
            ])
            ->assertSessionHasErrors(['document_type']);

        $this->assertDatabaseCount('qualifications', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_file_without_document_title_is_rejected(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();

        $this->actingAs($user)
            ->post(route('qualification.store'), [
                'person_id' => $person->id,
                'course' => 'BSc Accounting',
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => ['A'],
                // no document_title supplied
            ])
            ->assertSessionHasErrors(['document_title']);

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
                'file_name' => [
                    UploadedFile::fake()->create('malware.exe', 200, 'application/octet-stream'),
                ],
                'document_type' => ['A'],
                'document_title' => ['Malware File'],
            ])
            ->assertSessionHasErrors(['file_name.0']);

        $this->assertDatabaseCount('qualifications', 0);
        $this->assertDatabaseCount('documents', 0);
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
