<?php

namespace Tests\Feature;

use App\Enums\NoteTypeEnum;
use App\Models\InstitutionPerson;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected InstitutionPerson $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);

        // Create a staff member to attach notes to
        $this->staff = InstitutionPerson::factory()->create();
    }

    public function test_note_index_requires_authentication(): void
    {
        $response = $this->get(route('notes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_note_index_requires_permission(): void
    {
        $response = $this->actingAs($this->guestUser)
            ->get(route('notes.index'));

        $response->assertForbidden();
    }

    public function test_note_index_displays_notes_with_filters(): void
    {
        Note::factory()->count(3)->create([
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('notes.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Notes/Index')
                ->has('notes')
                ->has('noteTypes')
        );
    }

    public function test_note_store_creates_note(): void
    {
        $noteData = [
            'note' => 'Test note content for staff member',
            'note_type' => NoteTypeEnum::RETIRED->value,
            'note_date' => now()->format('Y-m-d'),
            'notable_id' => $this->staff->id,
            'notable_type' => InstitutionPerson::class,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('notes.store'), $noteData);

        $response->assertRedirect();
        $this->assertDatabaseHas('notes', [
            'note' => 'Test note content for staff member',
            'notable_id' => $this->staff->id,
        ]);
    }

    public function test_note_store_validates_notable_exists(): void
    {
        $noteData = [
            'note' => 'Test note',
            'notable_id' => 99999, // Non-existent ID
            'notable_type' => InstitutionPerson::class,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('notes.store'), $noteData);

        $response->assertSessionHasErrors('notable_id');
    }

    public function test_note_show_displays_note_details(): void
    {
        $note = Note::factory()->create([
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('notes.show', ['note' => $note->id]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Notes/Show')
                ->has('note')
        );
    }

    public function test_note_update_modifies_note(): void
    {
        $note = Note::factory()->create([
            'note' => 'Original note content',
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->patch(route('notes.update', ['note' => $note->id]), [
                'note' => 'Updated note content',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'note' => 'Updated note content',
        ]);
    }

    public function test_note_update_requires_permission(): void
    {
        $note = Note::factory()->create([
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->guestUser)
            ->patch(route('notes.update', ['note' => $note->id]), [
                'note' => 'Updated note content',
            ]);

        $response->assertForbidden();
    }

    public function test_note_destroy_soft_deletes_note(): void
    {
        $note = Note::factory()->create([
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('notes.delete', ['note' => $note->id]));

        $response->assertRedirect(route('notes.index'));
        $this->assertSoftDeleted('notes', ['id' => $note->id]);
    }
}
