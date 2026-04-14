<?php

namespace App\Http\Controllers;

use App\Enums\NoteTypeEnum;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Traits\LogsAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    use LogsAuthorization;

    /**
     * Display a listing of notes.
     */
    public function index(Request $request): Response
    {
        $this->logSuccess('viewed all notes');

        $notes = Note::query()
            ->with(['notable', 'documents'])
            ->when($request->note_type, fn ($q, $type) => $q->where('note_type', $type))
            ->when($request->notable_type, fn ($q, $type) => $q->where('notable_type', $type))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('note_date', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('note_date', '<=', $date))
            ->when($request->search, function ($q, $search) {
                $q->where('note', 'like', "%{$search}%");
            })
            ->latest('note_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Note $note) => [
                'id' => $note->id,
                'note' => $note->note,
                'note_type' => $note->note_type?->value,
                'note_type_label' => $note->note_type?->label(),
                'note_date' => $note->note_date?->format('d M Y'),
                'url' => $note->url,
                'notable_type' => $note->notable_type ? class_basename($note->notable_type) : null,
                'notable_id' => $note->notable_id,
                'notable_name' => $this->getNotableName($note),
                'documents_count' => $note->documents->count(),
                'created_at' => $note->created_at->format('d M Y H:i'),
            ]);

        // Get note types for filters
        $noteTypes = collect(NoteTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'filters' => $request->only(['search', 'note_type', 'notable_type', 'date_from', 'date_to']),
            'noteTypes' => $noteTypes,
        ]);
    }

    /**
     * Show the form for creating a new note.
     */
    public function create(): Response
    {
        $noteTypes = collect(NoteTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return Inertia::render('Notes/Create', [
            'noteTypes' => $noteTypes,
        ]);
    }

    /**
     * Store a newly created note in storage.
     */
    public function store(StoreNoteRequest $request)
    {
        DB::transaction(function () use ($request) {
            $newNote = $request->validated();
            $newNote['created_by'] = auth()->user()->id;
            $note = Note::create($newNote);

            $this->logSuccess('created a note', $note);
        });

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note): Response
    {
        $this->logSuccess('viewed note details', $note);

        $note->load(['notable', 'documents']);

        return Inertia::render('Notes/Show', [
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'note_type' => $note->note_type?->value,
                'note_type_label' => $note->note_type?->label(),
                'note_date' => $note->note_date?->format('d M Y'),
                'url' => $note->url,
                'notable_type' => $note->notable_type,
                'notable_id' => $note->notable_id,
                'notable_name' => $this->getNotableName($note),
                'created_by' => $note->created_by,
                'documents' => $note->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'document_title' => $doc->document_title,
                    'document_type' => $doc->document_type,
                    'file_name' => $doc->file_name,
                ]),
                'created_at' => $note->created_at->format('d M Y H:i'),
                'updated_at' => $note->updated_at->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified note.
     */
    public function edit(Note $note)
    {
        $noteTypes = collect(NoteTypeEnum::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        return [
            'note' => [
                'id' => $note->id,
                'note' => $note->note,
                'note_type' => $note->note_type?->value,
                'note_date' => $note->note_date?->format('Y-m-d'),
                'url' => $note->url,
            ],
            'noteTypes' => $noteTypes,
        ];
    }

    /**
     * Update the specified note in storage.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $note->update($request->validated());

        $this->logSuccess('updated a note', $note);

        return redirect()->back()->with('success', 'Note updated successfully.');
    }

    /**
     * Remove the specified note from storage.
     */
    public function delete(Note $note)
    {
        $this->logSuccess('deleted a note', $note);

        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Note deleted successfully.');
    }

    /**
     * Get the name of the notable entity.
     */
    private function getNotableName(Note $note): ?string
    {
        if (! $note->notable) {
            return null;
        }

        // Try common name properties
        if (isset($note->notable->name)) {
            return $note->notable->name;
        }

        if (isset($note->notable->full_name)) {
            return $note->notable->full_name;
        }

        // For InstitutionPerson, get the person's name
        if (method_exists($note->notable, 'person') && $note->notable->person) {
            return $note->notable->person->full_name;
        }

        return null;
    }
}
