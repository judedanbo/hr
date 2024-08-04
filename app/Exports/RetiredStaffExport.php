<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\Identity;
use App\Models\InstitutionPerson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RetiredStaffExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Rank',
            'Status',
            'Date Retired',
            'Ghana Card Number',
            'contact',
            'Emergency Contact',
        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentRank?->job?->name,
            $staff->statuses?->first()->status->label(),
            $staff->statuses->first()->start_date?->format('d F, Y'),
            $staff->person->identities->where('id_type', Identity::GhanaCard)->first()?->id_number,
            $staff->person->contacts->count() > 0 ?  $staff->person->contacts->where('contact_type', ContactTypeEnum::PHONE)->map(function ($item) {
                return $item->contact;
            })->implode(', ') : '',
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type ==  ContactTypeEnum::EMERGENCY;
            })->first()?->contact ?? '',
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->with(['person' => function ($query) {
                $query->with('contacts');
            }, 'statuses' => function ($query) {
                $query->latest('start_date');
            }])
            ->currentRank()
            ->retired();
    }
}
