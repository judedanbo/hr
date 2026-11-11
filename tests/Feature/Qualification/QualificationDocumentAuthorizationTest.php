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
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * The single-document routes: replace the document of a qualification, and
 * remove every document of one. They follow the same rule as attaching and
 * removing individual documents.
 */
class QualificationDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('qualifications-documents');
    }

    public function test_staff_cannot_replace_the_document_on_another_persons_qualification(): void
    {
        [$me] = $this->staffUser();
        $qualification = $this->qualificationOf(Person::factory()->create());
        $document = $this->attachDocument($qualification, 'Their certificate');

        $this->replaceDocument($me, $qualification)->assertForbidden();

        $this->assertSame('Their certificate', $document->fresh()->document_title);
        $this->assertSame(1, $qualification->documents()->count());
    }

    public function test_staff_cannot_add_a_document_to_another_persons_qualification(): void
    {
        [$me] = $this->staffUser();
        $qualification = $this->qualificationOf(Person::factory()->create());

        $this->replaceDocument($me, $qualification)->assertForbidden();

        $this->assertSame(0, $qualification->documents()->count());
        $this->assertSame([], Storage::disk('qualifications-documents')->allFiles());
    }

    public function test_the_refusal_comes_before_validation(): void
    {
        [$me] = $this->staffUser();
        $qualification = $this->qualificationOf(Person::factory()->create());

        $this->actingAs($me)
            ->post(route('qualification-document.update', ['qualification' => $qualification->id]), [])
            ->assertForbidden();
    }

    public function test_staff_cannot_remove_the_documents_of_another_persons_qualification(): void
    {
        [$me] = $this->staffUser();
        $qualification = $this->qualificationOf(Person::factory()->create());
        $document = $this->attachDocument($qualification);

        $this->actingAs($me)
            ->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertForbidden();

        $this->assertNotNull(Document::find($document->id));
    }

    public function test_staff_cannot_change_the_documents_of_their_own_approved_qualification(): void
    {
        [$me, $person] = $this->staffUser();
        $qualification = Qualification::factory()->approved()->create(['person_id' => $person->id]);
        $document = $this->attachDocument($qualification, 'Approved certificate');

        $this->replaceDocument($me, $qualification)->assertForbidden();
        $this->actingAs($me)
            ->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertForbidden();

        $this->assertSame('Approved certificate', $document->fresh()->document_title);
    }

    public function test_a_user_with_no_staff_record_cannot_change_documents(): void
    {
        $user = User::factory()->create(['person_id' => null, 'password_change_at' => now()]);
        $user->assignRole('staff');
        $qualification = $this->qualificationOf(Person::factory()->create());
        $document = $this->attachDocument($qualification);

        $this->replaceDocument($user, $qualification)->assertForbidden();
        $this->actingAs($user)
            ->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertForbidden();

        $this->assertNotNull(Document::find($document->id));
    }

    public function test_the_owner_can_add_a_document_to_their_pending_qualification(): void
    {
        [$me, $person] = $this->staffUser();
        $qualification = $this->qualificationOf($person);

        $this->replaceDocument($me, $qualification)->assertSessionHasNoErrors()->assertRedirect();

        $document = $qualification->documents()->sole();
        $this->assertSame('Replacement certificate', $document->document_title);
        Storage::disk('qualifications-documents')->assertExists($document->file_name);
    }

    public function test_the_owner_can_remove_the_documents_of_their_pending_qualification(): void
    {
        [$me, $person] = $this->staffUser();
        $qualification = $this->qualificationOf($person);
        $document = $this->attachDocument($qualification);

        $this->actingAs($me)
            ->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull(Document::find($document->id));
    }

    public function test_an_approver_can_change_the_documents_of_anyones_qualification(): void
    {
        $approver = User::factory()->create(['password_change_at' => now()]);
        $approver->givePermissionTo(Permission::firstOrCreate(['name' => 'approve staff qualification']));
        $qualification = Qualification::factory()->approved()->create(['person_id' => Person::factory()->create()->id]);
        $document = $this->attachDocument($qualification, 'Their certificate');

        $this->replaceDocument($approver, $qualification)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Replacement certificate', $document->fresh()->document_title);

        $this->actingAs($approver)
            ->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $this->assertNull(Document::find($document->id));
    }

    public function test_signed_out_visitors_are_sent_to_the_login_page(): void
    {
        $qualification = $this->qualificationOf(Person::factory()->create());
        $document = $this->attachDocument($qualification);

        $this->post(route('qualification-document.update', ['qualification' => $qualification->id]))
            ->assertRedirect(route('login'));
        $this->delete(route('qualification-document.delete', ['qualification' => $qualification->id]))
            ->assertRedirect(route('login'));

        $this->assertNotNull(Document::find($document->id));
    }

    private function replaceDocument(User $user, Qualification $qualification): TestResponse
    {
        return $this->actingAs($user)
            ->post(route('qualification-document.update', ['qualification' => $qualification->id]), [
                'document_type' => 'A',
                'document_title' => 'Replacement certificate',
                'file_name' => UploadedFile::fake()->create('replacement.pdf', 200, 'application/pdf'),
                'file_type' => 'application/pdf',
            ]);
    }

    private function qualificationOf(Person $person): Qualification
    {
        return Qualification::factory()->pending()->create(['person_id' => $person->id]);
    }

    private function attachDocument(Qualification $qualification, string $title = 'Certificate'): Document
    {
        return $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => $title,
            'document_status' => 'P',
            'file_name' => 'certificate.pdf',
            'file_type' => 'application/pdf',
        ]);
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
