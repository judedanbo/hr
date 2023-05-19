<?php

namespace App\Enums;

enum MaritalStatus: String
{
    case Single = 'S';
    case Married = 'M';
    case Widowed = 'W';
    case Divorced = 'D';
}
