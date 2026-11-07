<?php

namespace App\Services;

use App\Exceptions\SaleValidationException;
use App\Models\Sale;
use App\Models\Stock;
use App\Support\Money;

class SaleValidationService
{
    public function validateOrFail(Sale $sale): void
    {
        $sale->loadMissing(['customer', 'warehouse', 'items.product', 'items.chamber']);

        $errors = [];
        $stockErrors = [];

        if ($sale->items->isEmpty()) {
            $errors[] = 'La venta debe tener al menos un producto.';
        }

        if (! $sale->customer?->is_active || $sale->customer->trashed()) {
            $errors[] = 'El cliente no está activo.';
        }

        if (! $sale->warehouse?->is_active) {
            $errors[] = 'La bodega no está activa.';
        }

        $stocks = Stock::query()
            ->where('warehouse_id', $sale->warehouse_id)
            ->whereIn('product_id', $sale->items->pluck('product_id')->unique()->all())
            ->get();

        foreach ($sale->items as $item) {
            if (! $item->product || $item->product->trashed() || ! $item->product->is_active) {
                $errors[] = "El producto {$item->sku} no está disponible.";

                continue;
            }

            if (Money::compare($item->quantity, '0') <= 0) {
                $errors[] = "La cantidad de {$item->product_name} debe ser mayor que cero.";

                continue;
            }

            $stock = $stocks->first(function (Stock $stock) use ($item) {
                if ((int) $stock->product_id !== (int) $item->product_id) {
                    return false;
                }

                return $item->chamber_id
                    ? (int) $stock->chamber_id === (int) $item->chamber_id
                    : $stock->chamber_id === null;
            });

            $available = Money::subtract($stock?->quantity ?? 0, $stock?->reserved ?? 0);
            $requested = Money::quantity($item->quantity);

            if (Money::compare($available, $requested) < 0) {
                $scope = $item->chamber_id
                    ? ' en cámara '.($item->chamber?->name ?? $item->chamber_id)
                    : '';

                $stockErrors[] = [
                    'sku' => $item->sku,
                    'product' => $item->product_name,
                    'requested' => $requested,
                    'available' => Money::quantity($available),
                ];
                $errors[] = "Stock insuficiente para {$item->product_name}{$scope}: solicitado {$requested}, disponible ".Money::quantity($available).'.';
            }
        }

        if ($errors !== []) {
            throw new SaleValidationException(
                ['errors' => $errors, 'stock' => $stockErrors],
                'La venta no pasó la validación de stock y datos.',
            );
        }
    }
}
