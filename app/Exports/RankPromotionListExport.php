<?php

namespace App\Exports;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RankPromotionListExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldQueue,
    ShouldAutoSize,
    WithTitle
{
    use Exportable;
    public $rank;
    public $batch;
    public function __construct(Job $rank, string $batch = null)
    {
        $this->rank = $rank;
        $this->batch = $batch ?? now() <= Carbon::parse('April 1') || now() >= Carbon::parse('October 1') ? 'april' : 'october';
    }
    public function headings(): array
    {
        return [
            'File Number',
            'Staff Number',
            'Full Name',
            $this->rank->name . ' Date',
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
                $query->whereYear('job_staff.start_date', '<=', now()->year - 3);
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $this->rank->id);
            })
            ->when($this->batch == 'april', function ($query) {
                $query->where('job_staff.start_date', '<=', Carbon::parse('first day of April')->subYear(3));
                $query->where(function ($query) {
                    $query->whereRaw('month(job_staff.start_date) IN (1, 2, 3, 4)');
                    $query->orWhereRaw('month(job_staff.start_date) IN (11, 12)');
                });
                // $query->whereRaw('month(job_staff.start_date) IN (1, 2, 3, 4)');
            })
            ->when(request()->batch == 'october', function ($query) {
                $query->where('job_staff.start_date', '<=', Carbon::parse('first day of October')->subYear(3));
                $query->whereRaw('month(job_staff.start_date) IN (4,5, 6, 7, 8, 9, 10)');
            })
            ->with(['person', 'units', 'ranks']);
    }
}
