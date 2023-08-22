<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;

class InstitutionRankController extends Controller
{
    public function index(Institution $institution)
    {
        $institution->load('ranks');
        return $institution->ranks->map(fn ($rank) => [
            'value' => $rank->id,
            'label' => $rank->name,
        ]);
    }
}