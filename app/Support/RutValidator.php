<?php

namespace App\Support;

class RutValidator
{
    public static function isValid(string $type, ?string $value): bool
    {
        if (blank($value)) {
            return true;
        }

        return match (strtoupper($type)) {
            'RUT' => self::isValidRut($value),
            'RUC' => (bool) preg_match('/^\d{11}$/', preg_replace('/\D/', '', $value)),
            default => mb_strlen(trim($value)) >= 3 && mb_strlen(trim($value)) <= 30,
        };
    }

    public static function isValidRut(string $rut): bool
    {
        $rut = strtoupper(preg_replace('/[^0-9K]/', '', $rut));

        if (strlen($rut) < 2) {
            return false;
        }

        $body = substr($rut, 0, -1);
        $dv = substr($rut, -1);

        if (! ctype_digit($body)) {
            return false;
        }

        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += (int) $body[$i] * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $expected = 11 - ($sum % 11);
        $expectedDv = match ($expected) {
            11 => '0',
            10 => 'K',
            default => (string) $expected,
        };

        return $dv === $expectedDv;
    }

    public static function normalize(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strtoupper(trim($value));
    }

    public static function formatRut(?string $rut): ?string
    {
        if (blank($rut)) {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^0-9K]/', '', $rut));

        if (strlen($clean) < 2) {
            return $clean;
        }

        $body = substr($clean, 0, -1);
        $dv = substr($clean, -1);
        $formatted = '';

        while (strlen($body) > 3) {
            $formatted = '.'.substr($body, -3).$formatted;
            $body = substr($body, 0, -3);
        }

        return $body.$formatted.'-'.$dv;
    }
}
