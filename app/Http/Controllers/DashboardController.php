<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchViewModeRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $request->session()->reflash();

        /** @var User $user */
        $user = $request->user();

        if ($user->isMultiRoleStaff()) {
            return $this->routeMultiRoleUser($user, $request->session()->get('view_mode'));
        }

        if ($user->hasRole('staff')) {
            return $this->redirectToStaffLanding($user);
        }

        if ($user->canAccessAdminDashboard()) {
            return $this->redirectToAdminDashboard();
        }

        return redirect()->route('staff.index');
    }

    public function showChooser(Request $request): RedirectResponse|InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isMultiRoleStaff()) {
            return redirect()->route('dashboard');
        }

        $canAdmin = $user->canAccessAdminDashboard();

        return Inertia::render('Dashboard/ChooseMode', [
            'staffOption' => [
                'label' => 'View my staff record',
                'description' => 'Go to your personal staff page.',
                'mode' => 'staff',
            ],
            'otherOption' => [
                'label' => $canAdmin ? 'Go to admin dashboard' : 'Go to staff list',
                'description' => $canAdmin
                    ? 'Continue to the institution dashboard with your administrative permissions.'
                    : 'Continue to the staff directory.',
                'mode' => 'other',
            ],
        ]);
    }

    public function switchMode(SwitchViewModeRequest $request): RedirectResponse
    {
        $request->session()->put('view_mode', $request->validated('mode'));

        return redirect()->route('dashboard');
    }

    private function routeMultiRoleUser(User $user, ?string $mode): RedirectResponse
    {
        if ($mode === 'staff') {
            return $this->redirectToStaffLanding($user);
        }

        if ($mode === 'other') {
            return $this->redirectToOtherLanding($user);
        }

        return redirect()->route('dashboard.choose-mode');
    }

    private function redirectToOtherLanding(User $user): RedirectResponse
    {
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
