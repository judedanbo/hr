<?php

namespace App\Enums;

enum NoteTypeEnum: string
{
    case NOT_PROVIDED = '';
    case RETIRED = 'RE';
    case DECEASED = 'DE';
    case INTERDICTION = 'ID';
    case RESIGNED = 'RS';
    case VOL_RETIREMENT = 'VR';
    case DISMISSED = 'DM';
    case LEAVE_WITHOUT_PAY = 'LN';
    case LEAVE_WITH_PAY = 'LP';
    case LEAVE_ABSENCE = 'lA';
    case SICK_LEAVE = 'SL';
    case MATERNITY_LEAVE = 'LM';
    case ANNUAL_LEAVE = 'LA';
    case COMPASSIONATE_LEAVE = 'LC';
    case VACATED_POST = 'VP';
    case REACTIVATION = 'RA';
    case SUMMARY_DISMISSAL = 'SD';
    case END_OF_TERM = 'ET';
    case TERMINATION = "TE";



    public function label(): string
    {
        return match ($this) {
            self::RETIRED => 'Retired',
            self::DECEASED => 'Deceased',
            self::INTERDICTION => 'Interdiction',
            self::RESIGNED => 'Resigned',
            self::VOL_RETIREMENT => 'Voluntary Retirement',
            self::DISMISSED => 'Dismissed',
            self::LEAVE_WITHOUT_PAY => 'Leave without pay',
            self::LEAVE_WITH_PAY => 'Leave with pay',
            self::LEAVE_ABSENCE => 'Leave of absence',
            self::SICK_LEAVE => 'Sick leave',
            self::MATERNITY_LEAVE => 'Maternity leave',
            self::ANNUAL_LEAVE => 'Annual leave',
            self::COMPASSIONATE_LEAVE => 'Compassionate leave',
            self::VACATED_POST => 'Vacated post',
            self::REACTIVATION => 'Reactivation',
            self::SUMMARY_DISMISSAL => 'Summary dismissal',
            self::END_OF_TERM => 'End of term',
            self::TERMINATION => "Termination",
            self::NOT_PROVIDED => 'General',
            default => static::NOT_PROVIDED,
        };
    }
}