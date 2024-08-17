<?php

namespace App\Exports;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobStaff;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromotionListExport implements
    ShouldAutoSize,
    WithHeadings,
    FromQuery,
    WithMapping,
    ShouldQueue,
    WithTitle,
    WithStyles
{
    public $rank;
    public function __construct($rank = null)
    {
        $this->rank = $rank !== null ? Job::find($rank) : null;
    }

    use Exportable;
    /**
     * @return string
     */
    public function title(): string
    {
        return $this->rank ? $this->rank->name . ' Promotion List' : "Promotion List";
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function headings(): array
    {
        return [
            'Staff Number',
            'File Number',
            'Name',
            'Retirement Date',
            'Current Post',
            'Date Posted',
            'Current Promotion',
            'Date Promoted',
            // 'level'
        ];
    }

    public function map($staff): array
    {
        return [
            $staff->staff_number,
            $staff->file_number,
            $staff->person?->full_name,
            $staff->person?->date_of_birth ? $staff->person->date_of_birth->addYears(60)->format('d M Y') : null,
            $staff->units?->first()?->name,
            $staff->units?->first() ? $staff->units?->first()->pivot?->start_date?->format('d M Y') : null,
            $staff->ranks?->first()?->name,
            $staff->ranks?->first() ? $staff->ranks?->first()->pivot?->start_date?->format('d M Y') : null,
            // $staff->currentRank?->job?->category->level

        ];
    }

    public function columnFormats(): array
    {
        return [
            "A" => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'F' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'H' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }

    public function query()
    {
        return Job::find($this->rank->id)
            ->activeStaff()
            ->active() // TODO Check for staff who has exited this ranks
            ->when(request()->search, function ($query, $search) {
                $query->whereHas('person', function ($query) use ($search) {
                    $query->search($search);
                });
            })
            ->whereHas('ranks', function ($query) {
                $query->whereNull('job_staff.end_date');
                $query->where('job_staff.job_id', $this->rank->id);
                $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
            })
            ->with(['person', 'units', 'ranks']);
        // return InstitutionPerson::query()
        //     ->active()
        //     ->join('job_staff', 'institution_person.id', '=', 'job_staff.staff_id')
        // ->currentRank()
        //     // ->join('jobs', 'job_staff.job_id', '=', 'jobs.id')
        //     // ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        //     ->with(['person', 'units'])
        //     // ->orderByRaw('job_categories.level')
        //     ->when($this->rank !== null, function ($query) {
        //         $query->where('job_staff.job_id', $this->rank);
        //     })
        //     // ->whereNull('jobs.deleted_at')
        //     ->whereNull('job_staff.end_date')
        //     ->whereRaw("year(job_staff.start_date) < " . date('Y') - 3);
    }
}
