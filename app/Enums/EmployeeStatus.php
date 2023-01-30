<?php

namespace App\Enums;

enum EmployeeStatus: String
{
    case Active = "A";
    case Inactive = "I";
    case Suspended = "S";
    case Left = "L";
}