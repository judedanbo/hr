<?php

namespace App\Http\Controllers;

use App\Traits\LogsAuthorization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    use LogsAuthorization;

    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): Response
    {
        $this->logSuccess('viewed audit logs');

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->log_name, fn ($q, $logName) => $q->where('log_name', $logName))
            ->when($request->event, fn ($q, $event) => $q->where('event', $event))
            ->when($request->causer_type, fn ($q, $causerType) => $q->where('causer_type', $causerType))
            ->when($request->date_from, fn ($q, $dateFrom) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($request->date_to, fn ($q, $dateTo) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('log_name', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
                'subject_id' => $activity->subject_id,
                'causer_type' => $activity->causer_type ? class_basename($activity->causer_type) : null,
                'causer_id' => $activity->causer_id,
                'causer_name' => $activity->causer?->name ?? 'System',
                'properties' => $activity->properties?->toArray() ?? [],
                'created_at' => $activity->created_at->format('d M Y H:i:s'),
            ]);

        // Get unique values for filters
        $logNames = Activity::distinct()->pluck('log_name')->filter()->values();
        $events = Activity::distinct()->pluck('event')->filter()->values();

        return Inertia::render('AuditLog/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'log_name', 'event', 'causer_type', 'date_from', 'date_to']),
            'filterOptions' => [
                'logNames' => $logNames,
                'events' => $events,
            ],
        ]);
    }

    /**
     * Display the specified activity log.
     */
    public function show(Activity $auditLog): Response
    {
        $this->logSuccess('viewed audit log details', $auditLog);

        $auditLog->load(['causer', 'subject']);

        return Inertia::render('AuditLog/Show', [
            'activity' => [
                'id' => $auditLog->id,
                'log_name' => $auditLog->log_name,
                'description' => $auditLog->description,
                'event' => $auditLog->event,
                'subject_type' => $auditLog->subject_type,
                'subject_id' => $auditLog->subject_id,
                'subject_name' => $auditLog->subject?->name ?? $auditLog->subject?->full_name ?? null,
                'causer_type' => $auditLog->causer_type,
                'causer_id' => $auditLog->causer_id,
                'causer_name' => $auditLog->causer?->name ?? 'System',
                'properties' => $auditLog->properties?->toArray() ?? [],
                'batch_uuid' => $auditLog->batch_uuid,
                'created_at' => $auditLog->created_at->format('d M Y H:i:s'),
                'updated_at' => $auditLog->updated_at->format('d M Y H:i:s'),
            ],
        ]);
    }

    /**
     * Remove the specified activity log.
     */
    public function delete(Activity $auditLog)
    {
        $this->logSuccess('deleted audit log entry', $auditLog);

        $auditLog->delete();

        return redirect()->route('audit-log.index')->with('success', 'Audit log entry deleted successfully');
    }
}
