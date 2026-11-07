<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\SaleValidationException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function issueForSale(Sale $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user) {
            $sale->loadMissing('items');

            $errors = [];
            $stockErrors = [];

            foreach ($sale->items as $item) {
                $stockQuery = Stock::query()
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id);

                if ($item->chamber_id) {
                    $stockQuery->where('chamber_id', $item->chamber_id);
                } else {
                    $stockQuery->whereNull('chamber_id');
                }

                $stock = $stockQuery->lockForUpdate()->first();
                $available = Money::subtract($stock?->quantity ?? 0, $stock?->reserved ?? 0);

                if (Money::compare($available, $item->quantity) < 0) {
                    $stockErrors[] = [
                        'sku' => $item->sku,
                        'product' => $item->product_name,
                        'requested' => $item->quantity,
                        'available' => $available,
                    ];
                    $errors[] = "Stock insuficiente para {$item->product_name}.";
                }
            }

            if ($errors !== []) {
                throw new SaleValidationException(
                    ['errors' => $errors, 'stock' => $stockErrors],
                    'No se pudo descontar el inventario.',
                );
            }

            foreach ($sale->items as $item) {
                $stockQuery = Stock::query()
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id);

                if ($item->chamber_id) {
                    $stockQuery->where('chamber_id', $item->chamber_id);
                } else {
                    $stockQuery->whereNull('chamber_id');
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if (! $stock) {
                    $stock = Stock::create([
                        'warehouse_id' => $sale->warehouse_id,
                        'chamber_id' => $item->chamber_id,
                        'product_id' => $item->product_id,
                        'quantity' => 0,
                        'reserved' => 0,
                    ]);
                }

                $before = (string) $stock->quantity;
                $after = Money::quantity(Money::subtract($before, $item->quantity));
                $stock->update(['quantity' => $after]);

                $movement = StockMovement::create([
                    'warehouse_id' => $sale->warehouse_id,
                    'product_id' => $item->product_id,
                    'chamber_id' => $item->chamber_id,
                    'bin_id' => $item->bin_id,
                    'user_id' => $user->id,
                    'type' => StockMovementType::Salida,
                    'quantity' => '-'.$item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => $sale->getMorphClass(),
                    'reference_id' => $sale->id,
                    'note' => "Venta {$sale->number}",
                ]);

                $item->update(['stock_movement_id' => $movement->id]);
            }
        });
    }

    public function restoreForSale(Sale $sale, User $user): void
    {
        DB::transaction(function () use ($sale, $user) {
            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                $stockQuery = Stock::query()
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id);

                if ($item->chamber_id) {
                    $stockQuery->where('chamber_id', $item->chamber_id);
                } else {
                    $stockQuery->whereNull('chamber_id');
                }

                $stock = $stockQuery->lockForUpdate()->first();

                if (! $stock) {
                    $stock = Stock::create([
                        'warehouse_id' => $sale->warehouse_id,
                        'chamber_id' => $item->chamber_id,
                        'product_id' => $item->product_id,
                        'quantity' => 0,
                        'reserved' => 0,
                    ]);
                }

                $before = (string) $stock->quantity;
                $after = Money::quantity(Money::add($before, $item->quantity));
                $stock->update(['quantity' => $after]);

                StockMovement::create([
                    'warehouse_id' => $sale->warehouse_id,
                    'product_id' => $item->product_id,
                    'chamber_id' => $item->chamber_id,
                    'bin_id' => $item->bin_id,
                    'user_id' => $user->id,
                    'type' => StockMovementType::Anulacion,
                    'quantity' => $item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => $sale->getMorphClass(),
                    'reference_id' => $sale->id,
                    'note' => "Anulación venta {$sale->number}",
                ]);
            }
        });
    }

    public function adjust(Warehouse $warehouse, Product $product, mixed $newQuantity, User $user, ?string $note = null, ?int $binId = null, string $unit = 'kg', ?int $chamberId = null): Stock
    {
        return DB::transaction(function () use ($warehouse, $product, $newQuantity, $user, $note, $binId, $unit, $chamberId) {
            $stock = $this->lockedStock($warehouse->id, $product->id, $chamberId);
            $before = (string) $stock->quantity;
            $after = Money::quantity($newQuantity);
            $difference = Money::subtract($after, $before);

            if (Money::isZero($difference)) {
                return $stock;
            }

            $type = Money::compare($difference, '0') > 0
                ? StockMovementType::AjustePositivo
                : StockMovementType::AjusteNegativo;

            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'chamber_id' => $chamberId,
                'bin_id' => $binId,
                'user_id' => $user->id,
                'unit' => $unit,
                'type' => $type,
                'quantity' => $difference,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $product->cost_clp,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'note' => $note ?: 'Ajuste manual de inventario',
            ]);

            return $stock->fresh();
        });
    }

    public function receive(Warehouse $warehouse, Product $product, mixed $quantity, User $user, ?string $note = null, mixed $unitCost = null, ?int $binId = null, string $unit = 'kg', ?int $chamberId = null): Stock
    {
        return DB::transaction(function () use ($warehouse, $product, $quantity, $user, $note, $unitCost, $binId, $unit, $chamberId) {
            $quantity = Money::quantity($quantity);

            if (Money::compare($quantity, '0') <= 0) {
                throw new SaleValidationException(['errors' => ['La cantidad debe ser mayor que cero.']]);
            }

            $stock = $this->lockedStock($warehouse->id, $product->id, $chamberId);
            $before = (string) $stock->quantity;
            $after = Money::quantity(Money::add($before, $quantity));

            $stock->update(['quantity' => $after]);

            StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'chamber_id' => $chamberId,
                'bin_id' => $binId,
                'user_id' => $user->id,
                'unit' => $unit,
                'type' => StockMovementType::Entrada,
                'quantity' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $unitCost ?? $product->cost_clp,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'note' => $note ?: 'Ingreso de inventario',
            ]);

            return $stock->fresh();
        });
    }

    public function transfer(Warehouse $from, Warehouse $to, Product $product, mixed $quantity, User $user, ?string $note = null, ?int $binId = null, string $unit = 'kg', ?int $chamberId = null): void
    {
        if ($from->id === $to->id) {
            throw new SaleValidationException(['errors' => ['La bodega de origen y destino debe ser distinta.']]);
        }

        DB::transaction(function () use ($from, $to, $product, $quantity, $user, $note, $binId, $unit, $chamberId) {
            $quantity = Money::quantity($quantity);

            if (Money::compare($quantity, '0') <= 0) {
                throw new SaleValidationException(['errors' => ['La cantidad debe ser mayor que cero.']]);
            }

            $firstId = min($from->id, $to->id);
            $secondId = max($from->id, $to->id);
            $this->lockedStockById($firstId, $product->id);
            $this->lockedStockById($secondId, $product->id);

            $source = $this->lockedStock($from->id, $product->id, $chamberId);
            $target = $this->lockedStock($to->id, $product->id);

            if (Money::compare($source->quantity, $quantity) < 0) {
                throw new SaleValidationException(['errors' => ["Stock insuficiente en {$from->name} para trasladar {$product->name}."]]);
            }

            $sourceBefore = (string) $source->quantity;
            $sourceAfter = Money::quantity(Money::subtract($sourceBefore, $quantity));
            $source->update(['quantity' => $sourceAfter]);

            StockMovement::create([
                'warehouse_id' => $from->id,
                'product_id' => $product->id,
                'chamber_id' => $chamberId,
                'bin_id' => $binId,
                'user_id' => $user->id,
                'unit' => $unit,
                'type' => StockMovementType::TrasladoSalida,
                'quantity' => '-'.$quantity,
                'quantity_before' => $sourceBefore,
                'quantity_after' => $sourceAfter,
                'unit_cost' => $product->cost_clp,
                'reference_type' => Warehouse::class,
                'reference_id' => $to->id,
                'note' => $note ?: "Traslado a {$to->name}",
            ]);

            $targetBefore = (string) $target->quantity;
            $targetAfter = Money::quantity(Money::add($targetBefore, $quantity));
            $target->update(['quantity' => $targetAfter]);

            StockMovement::create([
                'warehouse_id' => $to->id,
                'product_id' => $product->id,
                'bin_id' => $binId,
                'user_id' => $user->id,
                'unit' => $unit,
                'type' => StockMovementType::TrasladoEntrada,
                'quantity' => $quantity,
                'quantity_before' => $targetBefore,
                'quantity_after' => $targetAfter,
                'unit_cost' => $product->cost_clp,
                'reference_type' => Warehouse::class,
                'reference_id' => $from->id,
                'note' => $note ?: "Traslado desde {$from->name}",
            ]);
        });
    }

    private function lockedStock(int $warehouseId, int $productId, ?int $chamberId = null): Stock
    {
        $stock = Stock::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'chamber_id' => $chamberId, 'product_id' => $productId],
            ['quantity' => 0, 'reserved' => 0],
        );

        return Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
    }

    private function lockedStockById(int $warehouseId, int $productId): ?Stock
    {
        return Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
    }
}
