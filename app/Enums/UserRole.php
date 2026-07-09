<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Accountant = 'accountant';
    case Employee = 'employee';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Accountant => 'Accountant',
            self::Employee => 'Employee',
            self::Auditor => 'Auditor',
        };
    }
}
