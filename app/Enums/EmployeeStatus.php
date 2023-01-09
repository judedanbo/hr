<?php
namespace App\Enums;

enum EmployeeStatus: String {
    case Active = "Active";
    case Suspended = "Suspended";
    case Left = "Left";
}