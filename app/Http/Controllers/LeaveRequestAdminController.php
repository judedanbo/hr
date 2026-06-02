<?php

namespace App\Http\Controllers;

use App\Models\LeaveDocument;
use App\Models\LeaveRequest;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LeaveRequest/All', [
            'requests' => LeaveRequest::query()
                ->with(['staff.person', 'leaveType', 'leaveYear'])
                ->when(request()->status, fn ($query, $status) => $query->where('status', $status))
                ->when(request()->search, function ($query, $search): void {
                    $query->whereHas('staff.person', function ($query) use ($search): void {
                        $query->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('surname', 'like', '%' . $search . '%');
                    });
                })
                ->latest('id')
                ->paginate()
                ->withQueryString()
                ->through(fn (LeaveRequest $leaveRequest): array => [
                    'id' => $leaveRequest->id,
                    'staff' => $leaveRequest->staff?->person?->full_name,
                    'leave_type' => $leaveRequest->leaveType?->name,
                    'year' => $leaveRequest->leaveYear?->year,
                    'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                    'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                    'requested_days' => $leaveRequest->requested_days,
                    'status' => $leaveRequest->status->value,
                ]),
            'filters' => request()->only('search', 'status'),
        ]);
    }

    public function show(LeaveRequest $leaveRequest): Response
    {
        $leaveRequest->load(['staff.person', 'leaveType', 'leaveYear', 'relievingOfficer.person', 'documents', 'statusHistories.changedBy']);

        return Inertia::render('LeaveRequest/AdminShow', [
            'request' => [
                'id' => $leaveRequest->id,
                'staff' => $leaveRequest->staff?->person?->full_name,
                'leave_type' => $leaveRequest->leaveType?->name,
                'year' => $leaveRequest->leaveYear?->year,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                'requested_days' => $leaveRequest->requested_days,
                'status' => $leaveRequest->status->value,
                'reason' => $leaveRequest->reason,
                'address_during_leave' => $leaveRequest->address_during_leave,
                'contact_during_leave' => $leaveRequest->contact_during_leave,
                'relieving_officer' => $leaveRequest->relievingOfficer?->person?->full_name,
                'documents' => $leaveRequest->documents->map(fn (LeaveDocument $document): array => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'file_type' => $document->file_type,
                ]),
                'history' => $leaveRequest->statusHistories->map(fn ($history): array => [
                    'from' => $history->from_status,
                    'to' => $history->to_status,
                    'reason' => $history->reason,
                    'by' => $history->changedBy?->name,
                    'at' => $history->created_at?->format('Y-m-d H:i'),
                ]),
            ],
        ]);
    }
}
