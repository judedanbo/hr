<?php

namespace App\Http\Controllers;

use App\Enums\DocumentTypeEnum;
use App\Http\Requests\UpdateQualificationDocumentRequest;
use App\Models\Document;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;

class QualificationDocumentController extends Controller
{
    public function store(Request $request, Qualification $qualification): mixed
    {
        abort_unless(
            $qualification->canBeEditedBy($request->user())
                || $request->user()?->can('approve staff qualification'),
            403,
        );

        $validated = $request->validate([
            'file_name' => 'required|array|min:1',
            'file_name.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document_type' => 'required|array|min:1',
            'document_type.*' => ['required', new Enum(DocumentTypeEnum::class)],
            'document_title' => 'required|array|min:1',
            'document_title.*' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($qualification, $request, $validated) {
            $types = $validated['document_type'];
            $titles = $validated['document_title'];

            foreach ($request->file('file_name') as $i => $file) {
                $path = Storage::disk('qualifications-documents')->put('/', $file);
                $qualification->documents()->create([
                    'document_type' => $types[$i] ?? null,
                    'document_title' => $titles[$i] ?? null,
                    'document_status' => 'P',
                    'file_name' => $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        });

        return back()->with('success', 'Documents attached.');
    }

    public function update(UpdateQualificationDocumentRequest $request, Qualification $qualification)
    {
        // return $request->validated();
        // $request->file('file_name')->store('public/qualifications');
        if ($qualification->documents->count() < 1) {
            $newDocument = $request->validated();
            $cv = Storage::disk('qualifications-documents')->put('/', $request->file_name);
            $newDocument['file_name'] = $cv;
            $qualification->documents()->create($newDocument);
        } else {
            // return $request->validated();
            // return $qualification->documents->first();
            $updateDocument = $request->validated();
            $updateDocument['file_name'] = $request->file('file_name')->hashName();
            $qualification->documents()->first()->update($updateDocument);
        }

        // $qualification->documents()->create([
        //     'document_type' => 'A',
        //     'document_title' => 'Qualification Document',
        //     // 'document_number' => $request->file('file_name')->hashName(),
        //     'file_name' => $request->file('file_name')->hashName(),
        //     'file_type' => $request->file('file_name')->getMimeType(),
        //     'document_status' => 'P',
        //     'document_remarks' => null,
        //     'documentable_type' => 'App\Models\Qualification',
        //     'documentable_id' => $qualification->id,
        // ]);
        return back()->with('success', 'Qualification Document has been uploaded.');
    }

    public function delete(Qualification $qualification)
    {
        $qualification->documents()->delete();

        return back()->with('success', 'Qualification Document has been deleted.');
    }

    public function destroy(Request $request, Qualification $qualification, Document $document): mixed
    {
        abort_unless(
            $document->documentable_id === $qualification->id
                && $document->documentable_type === Qualification::class,
            404,
        );

        abort_unless(
            $qualification->canBeEditedBy($request->user())
                || $request->user()?->can('approve staff qualification'),
            403,
        );

        if ($document->file_name && Storage::disk('qualifications-documents')->exists($document->file_name)) {
            Storage::disk('qualifications-documents')->delete($document->file_name);
        }

        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
