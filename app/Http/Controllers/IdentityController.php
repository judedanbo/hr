<?php

namespace App\Http\Controllers;

use App\Enums\Identity;

class IdentityController extends Controller
{
    public function index()
    {
        $identities = null;
        foreach (Identity::cases() as $type) {
            $newId = new \stdClass();
            $newId->value = $type;
            $newId->label = $type->label();
            $identities[] = $newId;
        }

        return $identities;
    }
}
