<?php

namespace App\Http\Controllers;

use App\Enums\GenderEnum;
use Illuminate\Http\Request;

class GenderController extends Controller
{
    public function index()
    {
        $gender = [];
        foreach (GenderEnum::cases() as $gen) {
            $newGender = new \stdClass();
            $newGender->value = $gen->value;
            $newGender->label = $gen->label();
            $gender[] = $newGender;
        }
        return $gender;
    }
}