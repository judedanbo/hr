<?php

namespace Tests\Feature\Qualification;

use App\Models\Document;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreQualificationDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_of_pending_qualification_can_attach_multiple_documents(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);

        $this->actingAs($user)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf'),
                    UploadedFile::fake()->create('transcript.pdf', 150, 'application/pdf'),
                    UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
                ],
                'document_type' => ['A', 'T', 'P'],
                'document_title' => ['Degree Certificate', 'Official Transcript', 'Professional Certificate'],
            ])
            ->assertSessionDoesntHaveErrors();

        $qualification->refresh();
        $this->assertCount(3, $qualification->documents);

        $types = $qualification->documents->pluck('document_type')->toArray();
        $this->assertContains('A', $types);
        $this->assertContains('T', $types);
        $this->assertContains('P', $types);

        $titles = $qualification->documents->pluck('document_title')->toArray();
        $this->assertContains('Degree Certificate', $titles);
        $this->assertContains('Official Transcript', $titles);
        $this->assertContains('Professional Certificate', $titles);

        $qualification->documents->each(function (Document $doc) {
            $this->assertSame('P', $doc->document_status);
            $this->assertNotEmpty($doc->file_name);
            Storage::disk('qualifications-documents')->assertExists($doc->file_name);
        });
    }

    public function test_attach_preserves_existing_documents(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);

        // Pre-existing document
        $existingPath = Storage::disk('qualifications-documents')->put('/', UploadedFile::fake()->create('existing.pdf', 100, 'application/pdf'));
        $existing = $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Original Certificate',
            'document_status' => 'P',
            'file_name' => $existingPath,
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('new1.pdf', 200, 'application/pdf'),
                    UploadedFile::fake()->create('new2.pdf', 150, 'application/pdf'),
                ],
                'document_type' => ['T', 'P'],
                'document_title' => ['New Transcript', 'New Certificate'],
            ])
            ->assertSessionDoesntHaveErrors();

        $qualification->refresh();
        $this->assertCount(3, $qualification->documents);

        // Existing document is untouched
        $this->assertNotNull(Document::find($existing->id));
        $refreshed = Document::find($existing->id);
        $this->assertSame('Original Certificate', $refreshed->document_title);
        $this->assertSame($existingPath, $refreshed->file_name);
    }

    public function test_staff_cannot_attach_to_another_persons_qualification(): void
    {
        Storage::fake('qualifications-documents');
        [$me] = $this->staffUser();
        $other = Person::factory()->create();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $other->id]);

        $this->actingAs($me)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => ['A'],
                'document_title' => ['Stolen Certificate'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_cannot_attach_documents_to_approved_qualification(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->approved()->create(['person_id' => $person->id]);

        $this->actingAs($me = $user)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => ['A'],
                'document_title' => ['My Certificate'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_type_and_title_are_required_per_file(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);

        // Missing document_type for first file
        $this->actingAs($user)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => [''],
                'document_title' => ['My Certificate'],
            ])
            ->assertSessionHasErrors(['document_type.0']);

        $this->assertDatabaseCount('documents', 0);

        // Missing document_title for first file
        $this->actingAs($user)
            ->post(route('qualification-document.store', ['qualification' => $qualification->id]), [
                'file_name' => [
                    UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'),
                ],
                'document_type' => ['A'],
                'document_title' => [''],
            ])
            ->assertSessionHasErrors(['document_title.0']);

        $this->assertDatabaseCount('documents', 0);
    }

    /**
     * @return array{User, Person}
     */
    private function staffUser(): array
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->assignRole('staff');

        return [$user, $person];
    }
}
