<?php

namespace App\Support\Organization;

class EmailDomain
{
    public static function fromEmail(string $email): ?string
    {
        $email = strtolower(trim($email));

        if (! str_contains($email, '@')) {
            return null;
        }

        $domain = substr(strrchr($email, '@'), 1);

        return $domain !== '' ? $domain : null;
    }

    public static function isGeneric(?string $domain): bool
    {
        if (! $domain) {
            return true;
        }

        return in_array(strtolower($domain), config('organization.generic_email_domains', []), true);
    }

    public static function isEligibleForAutoJoin(string $email): bool
    {
        $domain = self::fromEmail($email);

        return $domain !== null && ! self::isGeneric($domain);
    }

    public static function normalize(?string $domain): ?string
    {
        if (! $domain) {
            return null;
        }

        $domain = strtolower(trim($domain));
        $domain = ltrim($domain, '@');

        return $domain !== '' ? $domain : null;
    }
}
