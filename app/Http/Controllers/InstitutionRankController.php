<?php

namespace App\Http\Controllers;

use App\Models\Institution;

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
