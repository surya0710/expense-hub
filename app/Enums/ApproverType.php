<?php

namespace App\Enums;

enum ApproverType: string
{
    case Role = 'role';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Role => 'By role',
            self::User => 'Specific user',
        };
    }
}
