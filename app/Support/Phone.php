<?php

namespace App\Support;

/**
 * Telefon raqamini yagona ko'rinishga keltiradi: +998XXXXXXXXX.
 * "+998 90 123 45 67", "998901234567", "90 123 45 67" → "+998901234567".
 * Telefon bo'lmagan qiymat (email) o'zgarishsiz qaytadi.
 */
final class Phone
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if (str_contains($value, '@')) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return match (true) {
            strlen($digits) === 12 && str_starts_with($digits, '998') => '+'.$digits,
            strlen($digits) === 9 => '+998'.$digits,
            default => $value,
        };
    }
}
