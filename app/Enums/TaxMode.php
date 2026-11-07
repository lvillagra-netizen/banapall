<?php

namespace App\Enums;

enum TaxMode: string
{
    case Iva19 = 'iva_19';
    case Igv18 = 'igv_18';
    case Export0 = 'export_0';
    case Exempt = 'exempt';

    public function label(): string
    {
        return (string) config("banapal.taxes.{$this->value}.label", $this->value);
    }

    public function rate(): string
    {
        return (string) config("banapal.taxes.{$this->value}.rate", 0);
    }

    public function percentage(): string
    {
        return rtrim(rtrim(number_format((float) $this->rate() * 100, 2, '.', ''), '0'), '.');
    }

    public static function forCountry(string $countryCode): self
    {
        return match (strtoupper($countryCode)) {
            'CL' => self::Iva19,
            'PE' => self::Igv18,
            'US' => self::Export0,
            default => self::Export0,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $mode) => [$mode->value => $mode->label()])->all();
    }
}
