<?php

namespace App\Support\Tenant;

use App\Models\Company;

class CompanyContext
{
    protected static ?int $companyId = null;

    protected static ?Company $company = null;

    public static function set(?Company $company): void
    {
        static::$company = $company;
        static::$companyId = $company?->id;
    }

    public static function setId(?int $companyId): void
    {
        static::$companyId = $companyId;
        static::$company = null;
    }

    public static function id(): ?int
    {
        return static::$companyId;
    }

    public static function company(): ?Company
    {
        if (static::$company) {
            return static::$company;
        }

        if (static::$companyId) {
            static::$company = Company::query()->find(static::$companyId);
        }

        return static::$company;
    }

    public static function clear(): void
    {
        static::$companyId = null;
        static::$company = null;
    }

    public static function check(): bool
    {
        return static::$companyId !== null;
    }
}
