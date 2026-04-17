<?php

namespace App\Http\Controllers;

use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use App\Models\User;
use App\Notifications\QualificationPendingApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class QualificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Qualification/Index', [
            'qualifications' => Qualification::query()
                ->with(['person.institution', 'documents'])
                ->visibleTo($user)
                ->orderBy('created_at', 'desc')
                ->whereHas('person', function ($query) {
                    $query->whereHas('institution');
                })
                ->paginate()
                ->withQueryString()
                ->through(function ($qualification) {
                    return [
                        'id' => $qualification->id,
                        'person' => $qualification->person->full_name,
                        'staff_number' => $qualification->person->institution->first()->staff->staff_number,
                        'course' => $qualification->course,
                        'institution' => $qualification->institution,
                        'qualification' => $qualification->qualification,
                        'qualification_number' => $qualification->qualification_number,
                        'level' => $qualification->level ? QualificationLevelEnum::tryFrom($qualification->level)?->label() ?? $qualification->level : null,
                        'pk' => $qualification->pk,
                        'year' => $qualification->year,
                        'status' => $qualification->status?->label(),
                        'status_color' => $qualification->status?->color(),
                        'created_at' => $qualification->created_at,
                        'documents' => $qualification->documents->map(fn ($doc) => [
                            'id' => $doc->id,
                            'document_title' => $doc->document_title,
                            'file_name' => $doc->file_name,
                            'file_type' => $doc->file_type,
                        ]),
                    ];
                }),
            'filters' => request()->all('search'),
            'can' => [
                'approve' => $user->can('approve staff qualification'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreQualificationRequest $request)
    {
        $data = $request->validated();
        $data['status'] = QualificationStatusEnum::Pending;

        $qualification = DB::transaction(function () use ($request, $data) {
            $qualification = Qualification::create(
                collect($data)->except(['document_type', 'document_title', 'file_name'])->all()
            );

            if ($request->hasFile('file_name')) {
                $types = (array) $request->input('document_type', []);
                $titles = (array) $request->input('document_title', []);

                foreach ($request->file('file_name') as $i => $file) {
                    $path = Storage::disk('qualifications-documents')->put('/', $file);
                    $qualification->documents()->create([
                        'document_type' => $types[$i] ?? null,
                        'document_title' => $titles[$i] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'document_status' => 'P',
                        'file_name' => $path,
                        'file_type' => $file->getMimeType(),
                    ]);
                }
            }

            return $qualification;
        });

        // Send notification to all users with approve permission
        $qualification->load('person');
        $approvers = User::permission('approve staff qualification')->get();
        Notification::send($approvers, new QualificationPendingApprovalNotification($qualification));

        return redirect()->back()->with('success', 'Qualification added and pending approval.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Qualification $qualification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Qualification $qualification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateQualificationRequest $request, Qualification $qualification)
    {
        $user = auth()->user();

        // Only pending qualifications owned by the user can be edited
        if (! $qualification->canBeEditedBy($user)) {
            return redirect()->back()->with('error', 'You cannot edit this qualification.');
        }

        if ($request->validated()) {
            $qualification->update($request->validated());

            return redirect()->back()->with('success', 'Qualification updated.');
        }

        return redirect()->back()->with('error', 'Qualification not updated.');
    }

    /**
     * Delete a qualification.
     */
    public function delete($qualification)
    {
        $user = auth()->user();
        $qual = Qualification::find($qualification);

        if (! $qual) {
            return redirect()->back()->with('error', 'Qualification not found.');
        }

        // Only pending qualifications owned by the user can be deleted
        if (! $qual->canBeDeletedBy($user)) {
            return redirect()->back()->with('error', 'You cannot delete this qualification.');
        }

        $qual->delete();

        return redirect()->back()->with('success', 'Qualification deleted.');
    }

    /**
     * Approve a pending qualification.
     */
    public function approve(Qualification $qualification)
    {
        if ($qualification->status !== QualificationStatusEnum::Pending) {
            return redirect()->back()->with('error', 'Only pending qualifications can be approved.');
        }

        $qualification->update([
            'status' => QualificationStatusEnum::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Qualification approved.');
    }

    /**
     * Reject a pending qualification.
     */
    public function reject(Request $request, Qualification $qualification)
    {
        if ($qualification->status !== QualificationStatusEnum::Pending) {
            return redirect()->back()->with('error', 'Only pending qualifications can be rejected.');
        }

        $qualification->update([
            'status' => QualificationStatusEnum::Rejected,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Qualification rejected.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Qualification $qualification)
    {
        //
    }
}
