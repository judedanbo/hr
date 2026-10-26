<?php

namespace App\Http\Controllers;

use App\Contracts\Services\StaffPositionServiceInterface;
use App\Http\Requests\SyncPositionRolesRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class PositionRoleController extends Controller
{
    public function __construct(
        protected StaffPositionServiceInterface $staffPositionService
    ) {}

    /**
     * The position's current mapping plus the roles that may be mapped.
     */
    public function index(Position $position): JsonResponse
    {
        return response()->json([
            'roles' => $position->roles->pluck('name')->values(),
            'available' => Role::query()
                ->whereNotIn('name', ['staff', 'super-administrator'])
                ->orderBy('name')
                ->get(['name as value', 'name as label']),
        ]);
    }

    /**
     * Replace the mapping wholesale. A single sync (rather than
     * attach/detach) matches the checkbox control and lets the reconciliation
     * of current holders compute additions and removals in one step.
     */
    public function sync(SyncPositionRolesRequest $request, Position $position): RedirectResponse
    {
        $result = $this->staffPositionService->syncPositionRoles(
            $position,
            $request->validated('roles')
        );

        $redirect = redirect()->back()->with('success', 'Position roles updated successfully.');

        return $result['warnings'] === []
            ? $redirect
            : $redirect->with('warning', implode(' ', $result['warnings']));
    }
}
