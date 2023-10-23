<?php

namespace App\Enums;

enum Identity: string
{
    case NationalID = 'N';
    case Social_Security_Number = 'S';
    case Passport = 'P';
    case Driver_License = 'D';
    case TIN = 'T';
}
