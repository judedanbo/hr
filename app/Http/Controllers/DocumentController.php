<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Traits\LogsAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use LogsAuthorization;

    /**
     * Display a listing of documents.
     */
    public function index(Request $request): Response
    {
        $this->logSuccess('viewed all documents');

        $documents = Document::query()
            ->with('documentable')
            ->when($request->document_type, fn($q, $type) => $q->where('document_type', $type))
            ->when($request->document_status, fn($q, $status) => $q->where('document_status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where('document_title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn(Document $document) => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_type_label' => $this->getDocumentTypeLabel($document->document_type),
                'document_title' => $document->document_title,
                'document_number' => $document->document_number,
                'document_status' => $document->document_status,
                'document_status_label' => $this->getDocumentStatusLabel($document->document_status),
                'file_name' => $document->file_name,
                'documentable_type' => $document->documentable_type ? class_basename($document->documentable_type) : null,
                'documentable_id' => $document->documentable_id,
                'created_at' => $document->created_at?->format('d M Y'),
            ]);

        $documentTypes = collect(DocumentTypeEnum::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $documentStatuses = collect(DocumentStatusEnum::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ]);

        return Inertia::render('Document/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'document_type', 'document_status']),
            'documentTypes' => $documentTypes,
            'documentStatuses' => $documentStatuses,
        ]);
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(): Response
    {
        $documentTypes = collect(DocumentTypeEnum::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $documentStatuses = collect(DocumentStatusEnum::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ]);

        return Inertia::render('Document/Create', [
            'documentTypes' => $documentTypes,
            'documentStatuses' => $documentStatuses,
        ]);
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(StoreDocumentRequest $request)
    {
        $validated = $request->validated();

        // Handle file upload
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $path = $file->store('documents', 'public');
            $validated['document_file'] = $path;
            $validated['file_type'] = $file->getClientMimeType();
            $validated['file_name'] = $file->getClientOriginalName();
        }

        $document = Document::create($validated);

        $this->logSuccess('created a document', $document);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document): Response
    {
        $this->logSuccess('viewed document details', $document);

        $document->load('documentable');

        return Inertia::render('Document/Show', [
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_type_label' => $this->getDocumentTypeLabel($document->document_type),
                'document_title' => $document->document_title,
                'document_number' => $document->document_number,
                'document_file' => $document->document_file,
                'file_type' => $document->file_type,
                'file_name' => $document->file_name,
                'document_status' => $document->document_status,
                'document_status_label' => $this->getDocumentStatusLabel($document->document_status),
                'document_remarks' => $document->document_remarks,
                'documentable_type' => $document->documentable_type,
                'documentable_id' => $document->documentable_id,
                'created_at' => $document->created_at?->format('d M Y H:i'),
                'updated_at' => $document->updated_at?->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->logSuccess('downloaded a document', $document);

        if (! $document->document_file || ! Storage::disk('public')->exists($document->document_file)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $document->document_file,
            $document->file_name ?? 'document'
        );
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document)
    {
        $documentTypes = collect(DocumentTypeEnum::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $documentStatuses = collect(DocumentStatusEnum::cases())->map(fn($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ]);

        return [
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_title' => $document->document_title,
                'document_number' => $document->document_number,
                'document_status' => $document->document_status,
                'document_remarks' => $document->document_remarks,
            ],
            'documentTypes' => $documentTypes,
            'documentStatuses' => $documentStatuses,
        ];
    }

    /**
     * Update the specified document in storage.
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $validated = $request->validated();

        // Handle file replacement if new file provided
        if ($request->hasFile('file_name')) {
            // Delete old file if exists
            if ($document->document_file && Storage::disk('public')->exists($document->document_file)) {
                Storage::disk('public')->delete($document->document_file);
            }

            $file = $request->file('file_name');
            $path = $file->store('documents', 'public');
            $validated['document_file'] = $path;
            $validated['file_type'] = $file->getClientMimeType();
            $validated['file_name'] = $file->getClientOriginalName();
        }

        $document->update($validated);

        $this->logSuccess('updated a document', $document);

        return redirect()->back()->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document)
    {
        $this->logSuccess('deleted a document', $document);

        $document->delete();

        return redirect()->route('document.index')->with('success', 'Document deleted successfully.');
    }

    /**
     * Get document type label.
     */
    private function getDocumentTypeLabel(?string $type): ?string
    {
        if (! $type) {
            return null;
        }

        $enum = DocumentTypeEnum::tryFrom($type);

        return $enum?->label() ?? $type;
    }

    /**
     * Get document status label.
     */
    private function getDocumentStatusLabel(?string $status): ?string
    {
        if (! $status) {
            return null;
        }

        $enum = DocumentStatusEnum::tryFrom($status);

        return $enum?->label() ?? $status;
    }
}
