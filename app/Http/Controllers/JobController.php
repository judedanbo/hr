<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index($institution)
    {
        $jobs = Job::withCount('staff')
            ->where('institution_id', $institution)
        // ->where('name', 'like', 'Prin.Auditor%')
        ->get();
    }
}