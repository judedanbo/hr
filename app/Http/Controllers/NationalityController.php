<?php

namespace App\Http\Controllers;

use App\Enums\CountryEnum;

class NationalityController extends Controller
{
    public function index()
    {
        $nationality = null;
        foreach (CountryEnum::cases() as $county) {
            $newNation = new \stdClass;
            $newNation->value = $county->value;
            $newNation->label = $county->nationality();
            $nationality[] = $newNation;
        }

        return $nationality;
    }
}
