<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;

class PromotionsController extends Controller
{
    public function index(int $year)
    {
        return Inertia::render('Report/Promotions/Index', [
            'promotions' => JobStaff::query()
                ->with(['job'])
                ->selectRaw('Year(start_date) year, job_id, count(case when month(start_date) <= 6 then 1 end) as april, count(case when month(start_date) > 6 then 1 end) as october')
                ->groupByRaw('year, job_id')
                ->orderByRaw('year desc')
            // ->havingRaw("year <= " . $this->year - 3 )
                ->where('remarks', '<>', '1st Appointment')
            // ->whereYear('start_date','<=', $this->year - 3)
                ->paginate()
                ->through(fn ($promotion) => [
                    'year' => $promotion->year,
                    'job_id' => $promotion->job_id,
                    'job_name' => $promotion->job->name,
                    'april' => $promotion->april,
                    'october' => $promotion->october,
                ]),
            'filters' => ['search' => request()->search],
        ]);
    }
}
