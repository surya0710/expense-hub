<?php

namespace App\Enums;

enum Industry: string
{
    case Retail = 'retail';
    case Manufacturing = 'manufacturing';
    case Services = 'services';
    case Hospitality = 'hospitality';
    case Healthcare = 'healthcare';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Retail => 'Retail',
            self::Manufacturing => 'Manufacturing',
            self::Services => 'Services / Agency',
            self::Hospitality => 'Hospitality',
            self::Healthcare => 'Healthcare',
            self::Other => 'Other',
        };
    }
}
