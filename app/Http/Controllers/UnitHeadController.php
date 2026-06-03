<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitHeadController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('UnitHead/Index', [
            'units' => Unit::query()
                ->with('head.person')
                ->when(request()->search, fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%'))
                ->orderBy('name')
                ->paginate()
                ->withQueryString()
                ->through(fn (Unit $unit): array => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'type' => $unit->type?->value,
                    'head_staff_id' => $unit->head_staff_id,
                    'head' => $unit->head?->person?->full_name,
                ]),
            'staffOptions' => $this->staffOptions(),
            'filters' => request()->only('search'),
        ]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'head_staff_id' => ['nullable', 'integer', 'exists:institution_person,id'],
        ]);

        $unit->update(['head_staff_id' => $data['head_staff_id'] ?? null]);

        return redirect()->back()->with('success', 'Unit head updated.');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function staffOptions(): array
    {
        return InstitutionPerson::query()
            ->active()
            ->with('person')
            ->get()
            ->map(fn (InstitutionPerson $staff): array => [
                'value' => $staff->id,
                'label' => trim(($staff->person?->full_name ?? 'Staff') . ' — ' . $staff->staff_number),
            ])
            ->values()
            ->all();
    }
}
