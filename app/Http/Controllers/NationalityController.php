<?php

namespace App\Http\Controllers;

use App\Enums\CountryEnum;
use Illuminate\Http\Request;

class NationalityController extends Controller
{
    public function index()
    {
        $nationality = null;
        foreach (CountryEnum::cases() as $county) {
            $newNation = new \stdClass;
            $newNation->value = $county->value;
            $newNation->label = $county->label();
            $nationality[] = $newNation;
        }
        return $nationality;
    }
}