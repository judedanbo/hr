<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole('staff')) {
            return $this->redirectToStaffLanding($user);
        }

        if ($user->canAccessAdminDashboard()) {
            return $this->redirectToAdminDashboard();
        }

        return redirect()->route('staff.index');
    }

    private function redirectToStaffLanding(User $user): RedirectResponse
    {
        if ($user->person) {
            return redirect()->route(
                'staff.show',
                [$user->person->institution->first()->staff->id]
            );
        }

        return redirect()->route('staff.index');
    }

    private function redirectToAdminDashboard(): RedirectResponse
    {
        if (Institution::count() < 1) {
            session()->flash(
                'info',
                'No institution found. Please create an institution to proceed'
            );

            return redirect()->route('institution.index');
        }

        return redirect()->route('institution.show', [1]);
    }
}
