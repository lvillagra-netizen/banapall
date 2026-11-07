@extends('layouts.app')

@section('title', 'Inventario')
@section('eyebrow', 'Inventario · Stock por bodega')

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
    <div x-data="{ modal: null, product: '', warehouse: '', chamber: '', quantity: '', note: '' }">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="max-w-xl text-sm text-slate-500">Stock en tiempo real por bodega. Los ajustes, ingresos y traslados quedan registrados con usuario y fecha.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inventory.movements') }}" class="btn btn-secondary">Movimientos</a>
                @can('inventory.manage')
                    <button type="button" @click="modal = 'receive'" class="btn btn-primary">Ingresar stock</button>
                @endcan
            </div>
        </div>

        <div class="mb-5 grid gap-4 sm:grid-cols-3">
            <div class="stat-card">
                <p class="metric-label">Productos en bodega</p>
                <p class="mt-2 text-3xl font-semibold text-ink">{{ $stocks->total() }}</p>
                <p class="mt-1 text-xs text-slate-400">Con registro de stock</p>
            </div>
            <div class="stat-card">
                <p class="metric-label">Unidades en esta página</p>
                <p class="mt-2 text-3xl font-semibold text-ink">{{ rtrim(rtrim(number_format((float) $stocks->sum('quantity'), 2, ',', '.'), '0'), ',') }}</p>
                <p class="mt-1 text-xs text-slate-400">Página {{ $stocks->currentPage() }} de {{ $stocks->lastPage() }}</p>
            </div>
            <div class="stat-card">
                <p class="metric-label">Alertas de stock</p>
                <p class="mt-2 text-3xl font-semibold {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-avocado-700' }}">{{ $lowStockCount }}</p>
                <p class="mt-1 text-xs text-slate-400">En o bajo el mínimo</p>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.index') }}" class="card mb-5">
            <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="label">Bodega</label>
                    <select name="warehouse" class="input">
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected($selectedWarehouse === $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Cámara</label>
                    <select name="chamber" class="input">
                        <option value="">Todas</option>
                        @foreach ($chambers as $chamber)
                            <option value="{{ $chamber->id }}" @selected($selectedChamber === $chamber->id)>{{ $chamber->warehouse?->name }} · {{ $chamber->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Buscar producto</label>
                    <input name="search" value="{{ request('search') }}" class="input" placeholder="Nombre o SKU…">
                </div>
                <label class="flex items-end gap-2 pb-1 text-sm text-slate-600">
                    <input type="checkbox" name="low" value="1" @checked(request()->boolean('low')) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                    Solo alertas
                </label>
                <div class="flex items-end gap-2">
                    <button class="btn btn-primary btn-sm">Aplicar</button>
                    <a href="{{ route('inventory.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header">
                <h2 class="section-title">Existencias</h2>
                <span class="badge badge-neutral">{{ $stocks->total() }} registros</span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cámara</th>
                            <th>Categoría</th>
                            <th class="text-right">Disponible</th>
                            <th class="text-right">Reservado</th>
                            <th class="text-right">Mínimo</th>
                            <th>Estado</th>
                            @can('inventory.manage')<th class="text-right">Acciones</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            @php($isLow = (float) $stock->quantity <= (float) $stock->product?->min_stock)
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-800">{{ $stock->product?->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $stock->product?->sku }}</p>
                                </td>
                                <td class="text-xs text-slate-500">{{ $stock->chamber?->name ?? 'Sin cámara' }}</td>
                                <td class="text-xs">{{ $stock->product?->category?->name ?? 'Sin categoría' }}</td>
                                <td class="text-right font-semibold {{ $isLow ? 'text-amber-700' : 'text-slate-800' }}">{{ rtrim(rtrim(number_format((float) $stock->quantity, 3, ',', '.'), '0'), ',') }}</td>
                                <td class="text-right text-xs text-slate-400">{{ rtrim(rtrim(number_format((float) $stock->reserved, 3, ',', '.'), '0'), ',') }}</td>
                                <td class="text-right text-xs text-slate-400">{{ $stock->product?->min_stock }}</td>
                                <td><span class="badge {{ $isLow ? 'badge-warning' : 'badge-success' }}">{{ $isLow ? 'Bajo mínimo' : 'OK' }}</span></td>
                                @can('inventory.manage')
                                    <td class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <button type="button" @click="modal = 'adjust'; product = {{ $stock->product_id }}; warehouse = {{ $stock->warehouse_id }}; chamber = '{{ $stock->chamber_id ?? '' }}'; quantity = {{ (float) $stock->quantity }}; note = ''" class="btn btn-ghost btn-sm">Ajustar</button>
                                            <button type="button" @click="modal = 'transfer'; product = {{ $stock->product_id }}; warehouse = {{ $stock->warehouse_id }}; chamber = '{{ $stock->chamber_id ?? '' }}'; quantity = ''; note = ''" class="btn btn-ghost btn-sm">Trasladar</button>
                                        </div>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-14 text-center text-sm text-slate-400">No hay existencias con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-5">{{ $stocks->links() }}</div>

        @can('inventory.manage')
            <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="modal = null">
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h3 class="font-display text-lg font-semibold text-ink">
                            <span x-show="modal === 'adjust'">Ajustar inventario</span>
                            <span x-show="modal === 'receive'">Ingresar stock</span>
                            <span x-show="modal === 'transfer'">Trasladar stock</span>
                        </h3>
                        <button type="button" @click="modal = null" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">×</button>
                    </div>

                    <form x-show="modal === 'adjust'" method="POST" action="{{ route('inventory.adjust') }}" class="space-y-3 p-5">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">Bodega *</label>
                                <select name="warehouse_id" x-model="warehouse" class="input">
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Producto *</label>
                                <select name="product_id" x-model="product" class="input">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="label">Unidad *</label>
                            <select name="unit" class="input">
                                <option value="kg">Kilogramos (kg)</option>
                                <option value="g">Gramos (g)</option>
                                <option value="ton">Toneladas (ton)</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Bin (opcional)</label>
                            <select name="bin_id" class="input">
                                <option value="">Sin bin</option>
                                @foreach ($bins as $bin)
                                    <option value="{{ $bin->id }}">{{ $bin->code }} · {{ $bin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Cámara (opcional)</label>
                            <select name="chamber_id" x-model="chamber" class="input">
                                <option value="">Sin cámara</option>
                                @foreach ($chambers as $chamberOption)
                                    <option value="{{ $chamberOption->id }}">{{ $chamberOption->warehouse?->name }} · {{ $chamberOption->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="label">Nueva cantidad *</label>
                            <input type="number" name="new_quantity" x-model="quantity" min="0" step="0.001" required class="input text-right">
                            <p class="mt-1 text-[11px] text-slate-400">El sistema crea el movimiento de ajuste correspondiente.</p>
                        </div>
                        <div>
                            <label class="label">Nota</label>
                            <input name="note" x-model="note" class="input" placeholder="Motivo del ajuste">
                        </div>
                        <button class="btn btn-primary w-full">Guardar ajuste</button>
                    </form>

                    <form x-show="modal === 'receive'" method="POST" action="{{ route('inventory.receive') }}" class="space-y-3 p-5">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">Bodega *</label>
                                <select name="warehouse_id" x-model="warehouse" class="input">
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Producto *</label>
                                <select name="product_id" x-model="product" class="input">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        <div>
                            <label class="label">Unidad *</label>
                            <select name="unit" class="input">
                                <option value="kg">Kilogramos (kg)</option>
                                <option value="g">Gramos (g)</option>
                                <option value="ton">Toneladas (ton)</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Bin (opcional)</label>
                            <select name="bin_id" class="input">
                                <option value="">Sin bin</option>
                                @foreach ($bins as $bin)
                                    <option value="{{ $bin->id }}">{{ $bin->code }} · {{ $bin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Cámara (opcional)</label>
                            <select name="chamber_id" x-model="chamber" class="input">
                                <option value="">Sin cámara</option>
                                @foreach ($chambers as $chamberOption)
                                    <option value="{{ $chamberOption->id }}">{{ $chamberOption->warehouse?->name }} · {{ $chamberOption->name }}</option>
                                @endforeach
                            </select>
                        </div>

                            <div>
                                <label class="label">Cantidad *</label>
                                <input type="number" name="quantity" min="0.001" step="0.001" required class="input text-right">
                            </div>
                            <div>
                                <label class="label">Costo unitario CLP</label>
                                <input type="number" name="unit_cost" min="0" step="0.01" class="input text-right">
                            </div>
                        </div>
                        <div>
                            <label class="label">Nota</label>
                            <input name="note" class="input" placeholder="Guía de despacho, proveedor, lote…">
                        </div>
                        <button class="btn btn-primary w-full">Registrar ingreso</button>
                    </form>

                    <form x-show="modal === 'transfer'" method="POST" action="{{ route('inventory.transfer') }}" class="space-y-3 p-5">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">Bodega origen *</label>
                                <select name="from_warehouse_id" x-model="warehouse" class="input">
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Bodega destino *</label>
                                <select name="to_warehouse_id" class="input">
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">Producto *</label>
                                <select name="product_id" x-model="product" class="input">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Unidad *</label>
                                <select name="unit" class="input">
                                    <option value="kg">Kilogramos (kg)</option>
                                    <option value="g">Gramos (g)</option>
                                    <option value="ton">Toneladas (ton)</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Bin (opcional)</label>
                                <select name="bin_id" class="input">
                                    <option value="">Sin bin</option>
                                    @foreach ($bins as $bin)
                                        <option value="{{ $bin->id }}">{{ $bin->code }} · {{ $bin->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Cámara origen (opcional)</label>
                                <select name="chamber_id" x-model="chamber" class="input">
                                    <option value="">Sin cámara</option>
                                    @foreach ($chambers as $chamberOption)
                                        <option value="{{ $chamberOption->id }}">{{ $chamberOption->warehouse?->name }} · {{ $chamberOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label">Cantidad *</label>
                                <input type="number" name="quantity" x-model="quantity" min="0.001" step="0.001" required class="input text-right">
                            </div>
                        </div>
                        <div>
                            <label class="label">Nota</label>
                            <input name="note" class="input" placeholder="Motivo del traslado">
                        </div>
                        <button class="btn btn-primary w-full">Registrar traslado</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection
