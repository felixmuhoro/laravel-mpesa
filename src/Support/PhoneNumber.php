<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Support;

use FelixMuhoro\Mpesa\Exceptions\MpesaException;

class PhoneNumber
{
    /**
     * Normalise a Kenyan phone number to Safaricom's required 2547XXXXXXXX format.
     *
     * Accepts:
     *  - 0712345678       (local, leading 0)
     *  - 712345678        (9 digits, no leading zero)
     *  - 254712345678     (already canonical)
     *  - +254712345678    (with plus)
     *  - 254 712 345 678  (with spaces)
     *  - +254-712-345-678 (with hyphens)
     *
     * Throws MpesaException if the number cannot be normalised.
     */
    public static function format(string $phone): string
    {
        $cleaned = preg_replace('/[\s\-+]/', '', $phone) ?? '';

        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '0')) {
            $cleaned = '254' . substr($cleaned, 1);
        } elseif (strlen($cleaned) === 9 && str_starts_with($cleaned, '7')) {
            $cleaned = '254' . $cleaned;
        } elseif (strlen($cleaned) === 9 && str_starts_with($cleaned, '1')) {
            $cleaned = '254' . $cleaned;
        }

        if (
            strlen($cleaned) !== 12
            || ! ctype_digit($cleaned)
            || ! str_starts_with($cleaned, '254')
        ) {
            throw new MpesaException("Invalid Kenyan phone number: $phone");
        }

        return $cleaned;
    }

    public static function isValid(string $phone): bool
    {
        try {
            self::format($phone);
            return true;
        } catch (MpesaException) {
            return false;
        }
    }
}
