<?php

namespace App\Exports;

use App\Enums\ContactTypeEnum;
use App\Enums\EmployeeStatusEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllSeparatedExport implements FromQuery, WithMapping, WithHeadings, ShouldQueue, ShouldAutoSize
{
    use Exportable;

    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Rank',
            'Separation Date',
            'Years served',
            'Contact',
            'Type'

        ];
    }
    public function map($staff): array
    {
        return [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->currentRank?->job?->name,
            $staff->statuses->first()->start_date?->format('d F, Y'),
            $staff->hire_date->diff($staff->statuses->first()->start_date)->format('%y years'),
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type ==  ContactTypeEnum::PHONE;
            })->first()?->contact ?? '',
            $staff->statuses->first()->status->label(),
            $staff->person->contacts->filter(function ($contact) {
                return $contact->contact_type ==  ContactTypeEnum::EMERGENCY;
            })->first()?->contact ?? '',
        ];
    }
    function query()
    {
        return InstitutionPerson::query()
            ->with(['person.contacts', 'statuses' => function ($query) {
                $query->latest('start_date');
            }])
            ->currentRank()
            ->retired();
    }
}
