<?php

namespace App\Support;

class PackageTrackingCode
{
    public static function isValid(string $trackingCode): bool
    {
        $value = strtoupper(trim($trackingCode));

        return self::isS10($value) || self::isAgreedAlphanumeric($value);
    }

    public static function detectStandard(string $trackingCode): string
    {
        $value = strtoupper(trim($trackingCode));

        if (self::isS10($value)) {
            return 'UPU_S10';
        }

        return 'AGREED_ALPHANUMERIC';
    }

    private static function isS10(string $trackingCode): bool
    {
        return (bool) preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}$/', $trackingCode);
    }

    private static function isAgreedAlphanumeric(string $trackingCode): bool
    {
        return (bool) preg_match('/^[A-Z0-9][A-Z0-9-]{4,29}$/', $trackingCode);
    }
}
