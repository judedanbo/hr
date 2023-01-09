<?php

namespace App\Enums;

enum ContactType: int
{
    case  Email = 1;
    case  Phone = 2;
    case  Address = 3;
    case  GHPostGPS = 4;
}