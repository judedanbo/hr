<?php
namespace App\Enums;

use Illuminate\Validation\Rules\Enum;

enum ContactType: int{
    case  Email = 1;
    case  Phone = 2;
    case  Address = 3;
    case  GHPostGPS = 4;
}