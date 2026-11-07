<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use App\Support\Money;
use Illuminate\Support\Carbon;
use RuntimeException;

class ExchangeRateService
{
    private array $cache = [];

    public function rateFor(string $currency, Carbon|string|null $date = null): string
    {
        $code = strtoupper($currency);

        if ($code === Currency::CLP->value) {
            return '1';
        }

        $dateString = $date instanceof Carbon ? $date->toDateString() : ($date ?? today()->toDateString());
        $cacheKey = "{$code}|{$dateString}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $rate = ExchangeRate::query()
            ->latestFor($code, $dateString)
            ->value('rate_to_clp');

        if ($rate === null) {
            throw new RuntimeException("No existe un tipo de cambio para {$code} con fecha {$dateString}.");
        }

        return $this->cache[$cacheKey] = Money::round($rate, 'CLP');
    }

    public function convert(mixed $amount, string $from, string $to, Carbon|string|null $date = null): string
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return Money::round($amount, $to);
        }

        $clp = Money::multiply($amount, $this->rateFor($from, $date));

        if ($to === Currency::CLP->value) {
            return Money::round($clp, 'CLP');
        }

        return Money::round(Money::divide($clp, $this->rateFor($to, $date)), $to);
    }

    public function latestRates(): array
    {
        return collect(Currency::cases())
            ->mapWithKeys(function (Currency $currency) {
                if ($currency === Currency::CLP) {
                    return [$currency->value => '1'];
                }

                $rate = ExchangeRate::query()
                    ->latestFor($currency->value)
                    ->first();

                return [$currency->value => $rate?->rate_to_clp];
            })
            ->all();
    }

    public function store(string $currency, mixed $rateToClp, string $effectiveDate, ?string $source = null): ExchangeRate
    {
        $currency = strtoupper($currency);
        $rate = Money::round($rateToClp, 'CLP');

        if ($currency === Currency::CLP->value) {
            throw new RuntimeException('El peso chileno es la moneda base y no requiere tipo de cambio.');
        }

        if (Money::compare($rate, '0') <= 0) {
            throw new RuntimeException('El tipo de cambio debe ser mayor que cero.');
        }

        $this->cache = [];

        return ExchangeRate::updateOrCreate(
            ['currency_code' => $currency, 'effective_date' => $effectiveDate],
            ['rate_to_clp' => $rate, 'source' => $source],
        );
    }
}
