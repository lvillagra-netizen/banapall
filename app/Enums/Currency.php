<?php

namespace App\Enums;

enum Currency: string
{
    case CLP = 'CLP';
    case USD = 'USD';
    case PEN = 'PEN';

    public function label(): string
    {
        return (string) config("banapal.currencies.{$this->value}.label", $this->value);
    }

    public function symbol(): string
    {
        return (string) config("banapal.currencies.{$this->value}.symbol", $this->value);
    }

    public function decimals(): int
    {
        return (int) config("banapal.currencies.{$this->value}.decimals", 2);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $currency) => [$currency->value => $currency->label()])->all();
    }
}
