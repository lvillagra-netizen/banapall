<?php

namespace App\Support;

use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class Money
{
    public static function of(mixed $value): BigDecimal
    {
        return BigDecimal::of((string) ($value ?? '0'));
    }

    public static function decimals(Currency|string $currency): int
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;

        return (int) config("banapal.currencies.{$code}.decimals", 2);
    }

    public static function round(mixed $value, Currency|string $currency): string
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;

        return (string) self::of($value)->toScale(self::decimals($code), RoundingMode::HalfUp);
    }

    public static function quantity(mixed $value): string
    {
        return (string) self::of($value)->toScale(3, RoundingMode::HalfUp);
    }

    public static function add(mixed ...$values): string
    {
        return (string) collect($values)->reduce(
            fn (BigDecimal $carry, mixed $value) => $carry->plus(self::of($value)),
            BigDecimal::zero(),
        );
    }

    public static function addCurrency(Currency|string $currency, mixed ...$values): string
    {
        return self::round(self::add(...$values), $currency);
    }

    public static function subtract(mixed $a, mixed $b): string
    {
        return (string) self::of($a)->minus(self::of($b));
    }

    public static function subtractCurrency(Currency|string $currency, mixed $a, mixed $b): string
    {
        return self::round(self::subtract($a, $b), $currency);
    }

    public static function multiply(mixed $a, mixed $b): string
    {
        return (string) self::of($a)->multipliedBy(self::of($b));
    }

    public static function multiplyCurrency(Currency|string $currency, mixed $a, mixed $b): string
    {
        return self::round(self::multiply($a, $b), $currency);
    }

    public static function divide(mixed $a, mixed $b): string
    {
        return (string) self::of($a)->dividedBy(self::of($b), 12, RoundingMode::HalfUp);
    }

    public static function divideCurrency(Currency|string $currency, mixed $a, mixed $b): string
    {
        return self::round(self::divide($a, $b), $currency);
    }

    public static function compare(mixed $a, mixed $b): int
    {
        return self::of($a)->compareTo(self::of($b));
    }

    public static function isZero(mixed $value): bool
    {
        return self::of($value)->isZero();
    }

    public static function isNegative(mixed $value): bool
    {
        return self::of($value)->isNegative();
    }

    public static function format(mixed $value, Currency|string $currency): string
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;
        $decimals = self::decimals($code);
        $symbol = (string) config("banapal.currencies.{$code}.symbol", $code);
        $negative = self::of($value)->isNegative();
        $absolute = self::of($value)->abs();

        if ($code === 'CLP') {
            $number = number_format((float) (string) $absolute, $decimals, ',', '.');
        } else {
            $number = number_format((float) (string) $absolute, $decimals, '.', ',');
        }

        return ($negative ? '-' : '').$symbol.' '.$number;
    }

    public static function formatPlain(mixed $value, Currency|string $currency): string
    {
        $code = $currency instanceof Currency ? $currency->value : $currency;

        return number_format((float) (string) self::of($value), self::decimals($code), '.', '');
    }
}
