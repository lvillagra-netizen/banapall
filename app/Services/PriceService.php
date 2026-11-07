<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Product;
use App\Support\Money;
use RuntimeException;

class PriceService
{
    public function __construct(private readonly ExchangeRateService $exchangeRates)
    {
    }

    public function priceFor(Product $product, string $currency): string
    {
        $currency = strtoupper($currency);
        $explicit = $product->prices->firstWhere('currency_code', $currency);

        if ($explicit) {
            return Money::round($explicit->price, $currency);
        }

        $baseCurrency = (string) config('banapal.base_currency', 'CLP');
        $basePrice = $product->prices->firstWhere('currency_code', $baseCurrency)?->price;

        if (! $basePrice && Money::compare($product->cost_clp, '0') > 0) {
            $basePrice = Money::multiplyCurrency($baseCurrency, $product->cost_clp, '1.65');
        }

        if (! $basePrice) {
            throw new RuntimeException("El producto {$product->sku} no tiene un precio configurado.");
        }

        if ($currency === $baseCurrency) {
            return Money::round($basePrice, $currency);
        }

        return $this->exchangeRates->convert($basePrice, $baseCurrency, $currency);
    }

    public function allPrices(Product $product): array
    {
        return collect(Currency::cases())
            ->mapWithKeys(fn (Currency $currency) => [
                $currency->value => $this->priceFor($product, $currency->value),
            ])
            ->all();
    }
}
