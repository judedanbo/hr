<?php

namespace App\Http\Controllers;

use App\Models\InstitutionPerson;
use Illuminate\Http\Request;

class AgeController extends Controller
{
    public function staffAgeDistribution()
    {
        $rank = request()->rank ?? null;
        $unit = request()->unit ?? null;
        return InstitutionPerson::query()
            ->join('people', 'people.id', 'institution_person.person_id')
            ->whereNotNull('people.date_of_birth')
            ->whereNull('people.deleted_at')
            ->when($rank, function ($query, $rank) {
                $query->join('job_staff', 'job_staff.staff_id', 'institution_person.id');
                $query->where('job_staff.job_id', $rank);
                $query->whereNull('job_staff.end_date');
                $query->whereNull('job_staff.deleted_at');
            })
            ->when($unit, function ($query, $unit) {
                $query->join('staff_unit', 'staff_unit.staff_id', 'institution_person.id');
                $query->where('staff_unit.unit_id', $unit);
                $query->whereNull('staff_unit.end_date');
                $query->whereNull('staff_unit.deleted_at');
            })
            ->selectRaw("
            count(*) total_staff, 
            CASE WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') < 20 THEN '0-19'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 20 AND 29 THEN '20-29'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 30 AND 39 THEN '30-39'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 40 AND 49 THEN '40-49'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 50 AND 59 THEN '50-59'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 60 AND 69 THEN '60-69'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') BETWEEN 70 AND 79 THEN '70-79'
            WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') >= 80 THEN '80+'
            ELSE 'Unknown' END age_group")
            // DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),people.date_of_birth)),'%y') age_group")
            ->groupBy('age_group')
            ->orderBy('age_group')
            ->having('age_group', '>', 0)
            ->get();
    }
}
