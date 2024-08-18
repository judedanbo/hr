<?php

namespace App\Http\Controllers;

use App\Enums\UnitType;

class UnitTypeController extends Controller
{
    public function index()
    {
        $types = null;
        foreach (UnitType::cases() as $type) {
            $newType = new \stdClass();
            $newType->value = $type->value;
            $newType->label = $type->label();
            $types[] = $newType;
        }

        return $types;
    }
}
