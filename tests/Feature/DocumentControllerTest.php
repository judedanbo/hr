<?php

namespace Tests\Feature;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);

        Storage::fake('public');
    }

    public function test_document_index_requires_authentication(): void
    {
        $response = $this->get(route('document.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_document_index_requires_permission(): void
    {
        $response = $this->actingAs($this->guestUser)->get(route('document.index'));
        $response->assertForbidden();
    }

    public function test_document_index_displays_documents(): void
    {
        Document::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('document.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Document/Index')->has('documents')->has('documentTypes')->has('documentStatuses'));
    }

    public function test_document_index_filters_by_document_type(): void
    {
        Document::factory()->create(['document_type' => DocumentTypeEnum::AcademicCertificate->value]);
        Document::factory()->create(['document_type' => DocumentTypeEnum::Transcript->value]);

        $response = $this->actingAs($this->superAdmin)->get(route('document.index', ['document_type' => DocumentTypeEnum::AcademicCertificate->value]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Document/Index')->has('documents.data', 1));
    }

    public function test_document_store_uploads_file(): void
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1024);

        $documentData = [
            'document_type' => DocumentTypeEnum::AcademicCertificate->value,
            'document_title' => 'Test Document',
            'document_number' => 'DOC-001',
            'document_file' => $file,
            'document_status' => DocumentStatusEnum::Approved->value,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('document.store'), $documentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'document_title' => 'Test Document',
            'document_number' => 'DOC-001',
        ]);
        Storage::disk('public')->assertExists('documents/' . $file->hashName());
    }

    public function test_document_store_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('test-document.exe', 1024);

        $response = $this->actingAs($this->superAdmin)->post(route('document.store'), [
            'document_type' => DocumentTypeEnum::AcademicCertificate->value,
            'document_title' => 'Test Document',
            'document_file' => $file,
            'document_status' => DocumentStatusEnum::Approved->value,
        ]);

        $response->assertSessionHasErrors('document_file');
    }

    public function test_document_store_validates_document_type(): void
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1024);

        $response = $this->actingAs($this->superAdmin)->post(route('document.store'), [
            'document_type' => 'INVALID',
            'document_title' => 'Test Document',
            'document_file' => $file,
            'document_status' => DocumentStatusEnum::Approved->value,
        ]);

        $response->assertSessionHasErrors('document_type');
    }

    public function test_document_show_displays_document_details(): void
    {
        $document = Document::factory()->create();

        $response = $this->actingAs($this->superAdmin)->get(route('document.show', ['document' => $document->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Document/Show')->has('document'));
    }

    public function test_document_download_streams_file(): void
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1024);
        $path = $file->store('documents', 'public');

        $document = Document::factory()->create([
            'document_file' => $path,
            'file_name' => 'test-document.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('document.download', ['document' => $document->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_document_download_returns_404_for_missing_file(): void
    {
        $document = Document::factory()->create([
            'document_file' => 'documents/nonexistent.pdf',
            'file_name' => 'nonexistent.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('document.download', ['document' => $document->id]));

        $response->assertStatus(404);
    }

    public function test_document_update_modifies_document(): void
    {
        $document = Document::factory()->create(['document_title' => 'Old Title']);

        $response = $this->actingAs($this->superAdmin)->patch(route('document.update', ['document' => $document->id]), [
            'document_type' => DocumentTypeEnum::Transcript->value,
            'document_title' => 'New Title',
            'document_status' => DocumentStatusEnum::Approved->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'document_title' => 'New Title']);
    }

    public function test_document_update_replaces_file(): void
    {
        // Create initial document with file
        $oldFile = UploadedFile::fake()->create('old-document.pdf', 1024);
        $oldPath = $oldFile->store('documents', 'public');

        $document = Document::factory()->create([
            'document_file' => $oldPath,
            'file_name' => 'old-document.pdf',
        ]);

        // Upload new file
        $newFile = UploadedFile::fake()->create('new-document.pdf', 2048);

        $response = $this->actingAs($this->superAdmin)->patch(route('document.update', ['document' => $document->id]), [
            'document_type' => $document->document_type,
            'document_title' => $document->document_title,
            'document_status' => $document->document_status,
            'file_name' => $newFile,
        ]);

        $response->assertRedirect();

        $document->refresh();
        $this->assertEquals('new-document.pdf', $document->file_name);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_document_destroy_soft_deletes_document(): void
    {
        $document = Document::factory()->create();

        $response = $this->actingAs($this->superAdmin)->delete(route('document.destroy', ['document' => $document->id]));

        $response->assertRedirect(route('document.index'));
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
