<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;
use Illuminate\Http\Request;

class UnitTypeController extends Controller
{
    public function index()
    {
        $types  = null;
        foreach (UnitType::cases() as $type) {
            $newType = new \stdClass();
            $newType->value = $type->value;
            $newType->label = $type->name;
            $types[] = $newType;
        }
        return $types;
    }
}