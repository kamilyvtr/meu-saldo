<?php

namespace App\Support;

final class MerchantNormalizer
{
    /**
     * Normalize an establishment/description name for frequency matching.
     */
    public static function normalize(string $value): string
    {
        $value = mb_strtoupper(trim($value));

        // Remove digits and common transaction codes.
        $value = preg_replace('/\d+/u', ' ', $value) ?? $value;

        // Keep letters and spaces only.
        $value = preg_replace('/[^\p{L}\s]/u', ' ', $value) ?? $value;

        // Collapse whitespace.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
