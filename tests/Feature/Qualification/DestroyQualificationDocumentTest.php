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

class DestroyQualificationDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_delete_own_pending_qualification_document(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);
        $path = Storage::disk('qualifications-documents')->put('/', UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf'));
        $document = $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Certificate',
            'document_status' => 'P',
            'file_name' => $path,
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->delete(route('qualification-document.destroy', [
                'qualification' => $qualification->id,
                'document' => $document->id,
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Document::find($document->id));
        $this->assertFalse(Storage::disk('qualifications-documents')->exists($path));
    }

    public function test_staff_cannot_delete_another_persons_qualification_document(): void
    {
        Storage::fake('qualifications-documents');
        [$me] = $this->staffUser();
        $other = Person::factory()->create();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $other->id]);
        $document = $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Certificate',
            'document_status' => 'P',
            'file_name' => 'theirs.pdf',
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($me)
            ->delete(route('qualification-document.destroy', [
                'qualification' => $qualification->id,
                'document' => $document->id,
            ]))
            ->assertForbidden();

        $this->assertNotNull(Document::find($document->id));
    }

    public function test_staff_cannot_delete_document_on_approved_qualification(): void
    {
        Storage::fake('qualifications-documents');
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->approved()->create(['person_id' => $person->id]);
        $document = $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Certificate',
            'document_status' => 'P',
            'file_name' => 'mine.pdf',
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->delete(route('qualification-document.destroy', [
                'qualification' => $qualification->id,
                'document' => $document->id,
            ]))
            ->assertForbidden();

        $this->assertNotNull(Document::find($document->id));
    }

    public function test_returns_404_when_document_does_not_belong_to_qualification(): void
    {
        [$user, $person] = $this->staffUser();
        $qualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);
        $otherQualification = Qualification::factory()->pending()->create(['person_id' => $person->id]);
        $document = $otherQualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Wrong parent',
            'document_status' => 'P',
            'file_name' => 'other.pdf',
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($user)
            ->delete(route('qualification-document.destroy', [
                'qualification' => $qualification->id,
                'document' => $document->id,
            ]))
            ->assertNotFound();
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
