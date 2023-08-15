<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\ContactTypeEnum;

class ContactTypeController extends Controller
{
    public function index()
    {
        $types  = null;
        foreach (ContactTypeEnum::cases() as $type) {
            $newType = new \stdClass();
            $newType->value = $type->value;
            $newType->label = $type->label();
            $types[] = $newType;
        }
        return $types;
    }
}