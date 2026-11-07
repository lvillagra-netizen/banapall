<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\Product;
use App\Support\Money;

class SaleCalculator
{
    public function __construct(private readonly PriceService $prices)
    {
    }

    public function calculate(array $items, string $currency, TaxMode $taxMode, mixed $exchangeRate = 1): array
    {
        $products = Product::query()
            ->with('prices')
            ->whereIn('id', collect($items)->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $taxRate = $taxMode->rate();
        $calculatedItems = [];
        $subtotal = '0';
        $discountTotal = '0';
        $taxTotal = '0';
        $total = '0';

        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));

            if (! $product) {
                continue;
            }

            $quantity = Money::quantity($item['quantity'] ?? 0);

            if (Money::compare($quantity, '0') <= 0) {
                continue;
            }

            $unitPrice = $this->prices->priceFor($product, $currency);
            $lineSubtotal = Money::multiplyCurrency($currency, $quantity, $unitPrice);
            $discount = Money::round($item['discount_amount'] ?? 0, $currency);

            if (Money::compare($discount, '0') < 0) {
                $discount = '0';
            }

            if (Money::compare($discount, $lineSubtotal) > 0) {
                $discount = $lineSubtotal;
            }

            $taxable = Money::subtractCurrency($currency, $lineSubtotal, $discount);
            $tax = Money::multiplyCurrency($currency, $taxable, $taxRate);
            $lineTotal = Money::addCurrency($currency, $taxable, $tax);
            $unitCost = $currency === 'CLP'
                ? Money::round($product->cost_clp, $currency)
                : Money::divideCurrency($currency, $product->cost_clp, $exchangeRate);

            $calculatedItems[] = [
                'product_id' => $product->id,
                'chamber_id' => $item['chamber_id'] ?? null,
                'bin_id' => $item['bin_id'] ?? null,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $tax,
                'line_subtotal' => $lineSubtotal,
                'line_total' => $lineTotal,
                'unit_cost' => $unitCost,
            ];

            $subtotal = Money::addCurrency($currency, $subtotal, $lineSubtotal);
            $discountTotal = Money::addCurrency($currency, $discountTotal, $discount);
            $taxTotal = Money::addCurrency($currency, $taxTotal, $tax);
            $total = Money::addCurrency($currency, $total, $lineTotal);
        }

        return [
            'items' => $calculatedItems,
            'totals' => [
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ],
        ];
    }
}
