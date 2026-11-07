<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Exceptions\SaleValidationException;
use App\Models\Bin;
use App\Models\Chamber;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $selectedWarehouse = $request->integer('warehouse') ?: $warehouses->first()?->id;

        $query = Stock::query()
            ->with(['product.category', 'warehouse', 'chamber'])
            ->whereHas('product', fn ($product) => $product->where('is_active', true));

        if ($selectedWarehouse) {
            $query->where('warehouse_id', $selectedWarehouse);
        }

        if ($chamber = $request->integer('chamber')) {
            $query->where('chamber_id', $chamber);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('product', function ($product) use ($search) {
                $product->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        if ($request->boolean('low')) {
            $query->whereRaw('stocks.quantity <= (select min_stock from products where products.id = stocks.product_id)');
        }

        $stocks = $query->join('products', 'products.id', '=', 'stocks.product_id')
            ->orderBy('products.name')
            ->select('stocks.*')
            ->paginate(30)
            ->withQueryString();

        $lowStockCount = Stock::query()
            ->whereHas('product', fn ($product) => $product->where('is_active', true))
            ->whereRaw('stocks.quantity <= (select min_stock from products where products.id = stocks.product_id)')
            ->count();

        return view('inventory.index', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'products' => Product::query()->active()->orderBy('name')->get(),
            'selectedWarehouse' => $selectedWarehouse,
            'selectedChamber' => $request->integer('chamber'),
            'lowStockCount' => $lowStockCount,
            'bins' => Bin::query()->active()->orderBy('name')->get(),
            'chambers' => Chamber::query()->with('warehouse')->orderBy('name')->get(),
        ]);
    }

    public function movements(Request $request): View
    {
        $query = StockMovement::query()->with(['product', 'warehouse', 'user', 'approver'])->latest('created_at');

        if ($warehouse = $request->integer('warehouse')) {
            $query->where('warehouse_id', $warehouse);
        }

        if ($product = $request->integer('product')) {
            $query->where('product_id', $product);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($request->boolean('pending')) {
            $query->whereNull('approved_by');
        }

        return view('inventory.movements', [
            'movements' => $query->paginate(30)->withQueryString(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'products' => Product::query()->withTrashed()->orderBy('name')->get(),
            'types' => StockMovementType::cases(),
            'showPending' => $request->boolean('pending'),
        ]);
    }

    public function approve(Request $request, StockMovement $movement): RedirectResponse
    {
        $this->authorize('inventory.approve');

        $movement->update([
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        AuditLogger::record('inventory.movement.approved', $movement, [], ['movement_id' => $movement->id], 'Movimiento de inventario aprobado');

        return back()->with('success', 'Movimiento aprobado correctamente.');
    }

    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'chamber_id' => ['nullable', 'exists:chambers,id'],
            'bin_id' => ['nullable', 'exists:bins,id'],
            'unit' => ['required', 'in:kg,g,ton'],
            'new_quantity' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
            $product = Product::query()->findOrFail($data['product_id']);
            $this->ensureChamberBelongsTo($data['chamber_id'] ?? null, $warehouse);
            $quantityKg = $this->toKilograms($data['new_quantity'], $data['unit']);
            $stock = $inventory->adjust($warehouse, $product, $quantityKg, $request->user(), $data['note'] ?? null, $data['bin_id'] ?? null, $data['unit'], $data['chamber_id'] ?? null);
            AuditLogger::record('inventory.adjusted', $product, [], ['warehouse_id' => $warehouse->id, 'quantity' => (string) $stock->quantity], 'Ajuste de inventario');

            return back()->with('success', 'Inventario ajustado correctamente.');
        } catch (SaleValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }
    }

    public function receive(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'chamber_id' => ['nullable', 'exists:chambers,id'],
            'bin_id' => ['nullable', 'exists:bins,id'],
            'unit' => ['required', 'in:kg,g,ton'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
            $product = Product::query()->findOrFail($data['product_id']);
            $this->ensureChamberBelongsTo($data['chamber_id'] ?? null, $warehouse);
            $quantityKg = $this->toKilograms($data['quantity'], $data['unit']);
            $inventory->receive($warehouse, $product, $quantityKg, $request->user(), $data['note'] ?? null, $data['unit_cost'] ?? null, $data['bin_id'] ?? null, $data['unit'], $data['chamber_id'] ?? null);
            AuditLogger::record('inventory.received', $product, [], ['warehouse_id' => $warehouse->id, 'quantity' => $data['quantity']], 'Ingreso de inventario');

            return back()->with('success', 'Ingreso de inventario registrado.');
        } catch (SaleValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }
    }

    public function transfer(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'product_id' => ['required', 'exists:products,id'],
            'chamber_id' => ['nullable', 'exists:chambers,id'],
            'bin_id' => ['nullable', 'exists:bins,id'],
            'unit' => ['required', 'in:kg,g,ton'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $from = Warehouse::query()->findOrFail($data['from_warehouse_id']);
            $this->ensureChamberBelongsTo($data['chamber_id'] ?? null, $from);
            $quantityKg = $this->toKilograms($data['quantity'], $data['unit']);
            $inventory->transfer(
                $from,
                Warehouse::query()->findOrFail($data['to_warehouse_id']),
                Product::query()->findOrFail($data['product_id']),
                $quantityKg,
                $request->user(),
                $data['note'] ?? null,
                $data['bin_id'] ?? null,
                $data['unit'],
                $data['chamber_id'] ?? null,
            );
            AuditLogger::record('inventory.transferred', null, [], $data, 'Traslado de inventario');

            return back()->with('success', 'Traslado de inventario registrado.');
        } catch (SaleValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', $exception->getMessage());
        }
    }

    private function ensureChamberBelongsTo(mixed $chamberId, Warehouse $warehouse): void
    {
        if (! $chamberId) {
            return;
        }

        $chamber = Chamber::query()->find($chamberId);

        if (! $chamber || (int) $chamber->warehouse_id !== (int) $warehouse->id) {
            throw new SaleValidationException([
                'errors' => ['La cámara seleccionada no pertenece a la bodega elegida.'],
            ]);
        }
    }

    private function toKilograms(mixed $value, string $unit): string
    {
        $value = (float) $value;

        return match ($unit) {
            'g' => (string) ($value / 1000),
            'ton' => (string) ($value * 1000),
            default => (string) $value,
        };
    }
}
