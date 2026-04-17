<?php

namespace App\Http\Controllers;

use App\Support\NotificationMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $status = $request->string('status')->toString();
        $status = in_array($status, ['unread', 'read'], true) ? $status : 'all';

        $type = $request->string('type')->toString();
        $type = $type !== '' ? $type : null;

        $query = $user->notifications();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        $paginator = $query->paginate(20)->withQueryString();

        $items = $paginator->through(fn (DatabaseNotification $notification) => $this->formatNotification($notification));

        $userTypes = $user->notifications()
            ->getQuery()
            ->reorder()
            ->distinct()
            ->pluck('type')
            ->map(fn (string $fqcn) => [
                'value' => $fqcn,
                'label' => NotificationMeta::label($fqcn),
            ])
            ->values()
            ->all();

        return Inertia::render('Notifications/Index', [
            'notifications' => $items,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'types' => $userTypes,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->formatNotification($notification))
            ->all();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $this->findForUser($request, $id);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $this->findForUser($request, $id);
        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    protected function findForUser(Request $request, string $id): DatabaseNotification
    {
        $notification = $request->user()->notifications()->find($id);

        abort_if($notification === null, HttpResponse::HTTP_NOT_FOUND);

        return $notification;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatNotification(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'type_label' => NotificationMeta::label($notification->type),
            'title' => $data['title'] ?? NotificationMeta::label($notification->type),
            'body' => $data['body'] ?? null,
            'icon' => $data['icon'] ?? 'bell',
            'url' => $data['url'] ?? null,
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_at_human' => $notification->created_at?->diffForHumans(),
        ];
    }
}
