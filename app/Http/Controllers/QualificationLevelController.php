<?php

namespace App\Http\Controllers;

use App\Enums\QualificationLevelEnum;

class QualificationLevelController extends Controller
{
    public function index()
    {
        $levels = null;
        foreach (QualificationLevelEnum::cases() as $level) {
            $newLevel = new \stdClass;
            $newLevel->value = $level->value;
            $newLevel->label = $level->label();
            $levels[] = $newLevel;
        }

        return $levels;
    }
}
