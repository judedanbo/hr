<?php

namespace App\Enums;

enum UnitType: string
{
    case Department = 'DEP';
    case Division = 'DIV';
    case Unit = 'MU';
    case Section = 'SEC';
    case Ministry = 'MIN';
    case Business_Unit = 'BU';
    case Branch = 'BRH';
}
