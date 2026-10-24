<?php

namespace App\Http\Controllers;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class StaffLookupController extends Controller
{
    /**
     * Return employment, personal and contact details for one active staff member.
     */
    public function show(string $staffNumber): JsonResponse
    {
        $staff = InstitutionPerson::query()
            ->active()
            ->whereHas('person')
            ->where('staff_number', $staffNumber)
            ->with([
                'person.contacts' => function ($query) {
                    $query->whereIn('contact_type', [ContactTypeEnum::EMAIL, ContactTypeEnum::PHONE]);
                    $query->where(function ($dateQuery) {
                        $dateQuery->whereNull('valid_end');
                        $dateQuery->orWhere('valid_end', '>=', today());
                    });
                },
                'ranks' => function ($query) {
                    $query->wherePivotNull('end_date');
                    $query->with('category:id,name');
                },
                'units' => function ($query) {
                    $query->wherePivotNull('end_date');
                    $query->with('parent:id,name');
                },
                'statuses' => function ($query) {
                    $query->where('status', 'A');
                    $query->where(function ($dateQuery) {
                        $dateQuery->whereNull('end_date');
                        $dateQuery->orWhere('end_date', '>', now());
                    });
                },
                'type' => function ($query) {
                    $query->where(function ($dateQuery) {
                        $dateQuery->whereNull('end_date');
                        $dateQuery->orWhere('end_date', '>', now());
                    });
                },
            ])
            ->first();

        if ($staff === null) {
            return response()->json(['message' => 'Staff not found.'], 404);
        }

        $person = $staff->person;
        $rank = $staff->ranks->first();
        $unit = $staff->units->first();
        $status = $staff->statuses->first();
        $staffType = $staff->type->first();

        return response()->json([
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'title' => $person->title,
            'first_name' => $person->first_name,
            'other_names' => $person->other_names,
            'surname' => $person->surname,
            'gender' => $person->gender?->label(),
            'date_of_birth' => $person->date_of_birth?->toDateString(),
            'age' => $person->date_of_birth ? $person->age : null,
            'retirement_date' => $staff->retirement_date?->toDateString(),
            'marital_status' => $person->marital_status?->label(),
            'nationality' => $person->nationality?->nationality(),
            'hire_date' => $staff->hire_date?->toDateString(),
            'status' => $status?->status->label(),
            'staff_type' => $staffType?->staff_type->label(),
            'rank' => $rank?->name,
            'job_category' => $rank?->category?->name,
            'unit' => $unit?->name,
            'department' => $unit?->parent?->name,
            'emails' => $this->contactValues($person->contacts, ContactTypeEnum::EMAIL),
            'phones' => $this->contactValues($person->contacts, ContactTypeEnum::PHONE),
        ]);
    }

    /**
     * Pluck the contact values of a single type.
     *
     * @param  Collection<int, Contact>  $contacts
     * @return array<int, string>
     */
    protected function contactValues(Collection $contacts, ContactTypeEnum $type): array
    {
        return $contacts
            ->filter(fn (Contact $contact) => $contact->contact_type === $type)
            ->pluck('contact')
            ->values()
            ->all();
    }
}
