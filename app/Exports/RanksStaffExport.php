<?php

namespace App\Exports;

use App\Models\Job;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RanksStaffExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldQueue,
    ShouldAutoSize,
    WithTitle
{
    use Exportable;
    public $rank;
    public function __construct(Job $rank)
    {
        $this->rank = $rank;
    }
    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            'Promotion Date',
            'Current Posting',
            'Posting Date',
        ];
    }
    public function title(): string
    {
        return Str::of($this->rank->name)->plural();
    }
    public function map($staff): array
    {
        return  [
            $staff->file_number,
            $staff->staff_number,
            $staff->person->full_name,
            $staff->ranks->first()?->pivot->start_date?->format('d M, Y'),
            $staff->units->first()?->name,
            $staff->units->first()?->pivot->start_date?->format('d M, Y'),
        ];
    }
    public function query()
    {
        return Job::find($this->rank->id)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->whereHas('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $this->rank->id);
            })
            ->with(['person', 'units', 'ranks']);
    }
}
