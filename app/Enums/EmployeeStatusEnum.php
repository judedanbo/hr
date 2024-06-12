<?php

namespace App\Enums;

enum EmployeeStatusEnum: String
{
    case Active = 'A';
    case Inactive = 'I';
    case Suspended = 'S';
    case Left = 'L';
    case Termination = 'T';
    case Resignation = 'R';
    case Voluntary = 'V';
    case Deceased = 'D';
    case Retired = 'E';
    case Dismissed = 'M';
    case leavePay = 'P';
    case leaveNoPay = 'N';
    case Vacation = 'C';

    public  function label(): string
    {
        return match ($this) {
            self::Active => 'Active staff',
            self::Inactive => 'Inactive staff',
            self::Suspended => 'Suspended staff',
            self::Left => 'Separated staff',
            self::Termination => 'Appointment Terminated',
            self::Resignation => 'Resigned staff',
            self::Voluntary => 'Voluntary Resignation',
            self::Deceased => 'Deceased',
            self::Retired => 'Statutory Retirement',
            self::Dismissed => 'Dismissed',
            self::leavePay => 'Leave with pay',
            self::leaveNoPay => 'Leave without pay',
            self::Vacation => 'Vacation of post',
        };
    }
}