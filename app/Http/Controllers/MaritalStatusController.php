<?php

namespace App\Http\Controllers;

use App\Enums\MaritalStatusEnum;
use Illuminate\Http\Request;

class MaritalStatusController extends Controller
{
    public function index()
    {
        $statues = null;

        foreach (MaritalStatusEnum::cases() as $status) {
            $sta =  new \stdClass;
            $sta->value = $status->value;
            $sta->label = $status->label();
            $statues[] = $sta;
        }
        return $statues;
    }
}