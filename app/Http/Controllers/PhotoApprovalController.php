<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PhotoApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('approve staff photo'), HttpResponse::HTTP_FORBIDDEN);

        $pending = Person::query()
            ->whereNotNull('pending_image')
            ->get()
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'name' => $person->full_name,
                'current_image' => $person->image ? '/storage/' . $person->image : null,
                'pending_image' => '/storage/' . $person->pending_image,
                'pending_image_at' => $person->pending_image_at?->diffForHumans(),
            ])
            ->all();

        return Inertia::render('PhotoApprovals/Index', [
            'pending' => $pending,
        ]);
    }

    public function approve(Request $request, Person $person): RedirectResponse
    {
        abort_unless($request->user()->can('approve staff photo'), HttpResponse::HTTP_FORBIDDEN);
        abort_if($person->pending_image === null, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'No pending photo to approve.');

        // Delete the previously approved file if one exists.
        if ($person->image) {
            Storage::disk('public')->delete($person->image);
        }

        $person->update([
            'image' => $person->pending_image,
            'pending_image' => null,
            'pending_image_at' => null,
            'image_approved_by' => $request->user()->id,
            'image_approved_at' => now(),
        ]);

        // Notify the staff member whose photo was approved.
        $staffUser = $person->user;
        if ($staffUser) {
            $staffUser->notify(new PhotoApprovedNotification($person));
        }

        return back()->with('success', 'Photo approved successfully.');
    }

    public function reject(Request $request, Person $person): RedirectResponse
    {
        abort_unless($request->user()->can('approve staff photo'), HttpResponse::HTTP_FORBIDDEN);
        abort_if($person->pending_image === null, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'No pending photo to reject.');

        Storage::disk('public')->delete($person->pending_image);

        $person->update([
            'pending_image' => null,
            'pending_image_at' => null,
        ]);

        // Notify the staff member whose photo was rejected.
        $staffUser = $person->user;
        if ($staffUser) {
            $staffUser->notify(new PhotoRejectedNotification($person));
        }

        return back()->with('success', 'Photo rejected and removed.');
    }
}
