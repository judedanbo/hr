<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateQualificationDocumentRequest;
use App\Models\Qualification;
use Illuminate\Http\Request;

class QualificationDocument extends Controller
{
    public function update(UpdateQualificationDocumentRequest
    $request, Qualification $qualification)
    {
        $request->file('document_file')->store('public/qualifications');
        $qualification->documents()->create([
            'document_type' => 'A',
            'document_title' => 'Qualification Document',
            // 'document_number' => $request->file('document_file')->hashName(),
            'document_file' => $request->file('document_file')->hashName(),
            'file_type' => $request->file('document_file')->getMimeType(),
            'document_status' => 'P',
            'document_remarks' => null,
            'documentable_type' => 'App\Models\Qualification',
            'documentable_id' => $qualification->id,
        ]);
        return back()->with('success', 'Qualification Document has been uploaded.');
    }
}
