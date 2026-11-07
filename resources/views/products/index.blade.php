@extends('layouts.app')

@section('title', 'Productos')
@section('eyebrow', 'Catálogo · Productos y precios')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Precios por moneda, costo base en CLP y stock consolidado entre bodegas.</p>
        <div class="flex gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Categorías</a>
            @can('products.manage')
                <a href="{{ route('products.create') }}" class="btn btn-primary">Nuevo producto</a>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="label">Buscar</label>
                <input name="search" value="{{ request('search') }}" class="input" placeholder="Nombre, SKU o variedad…">
            </div>
            <div>
                <label class="label">Categoría</label>
                <select name="category" class="input">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Estado</label>
                <select name="active" class="input">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('active') === '1')>Activos</option>
                    <option value="0" @selected(request('active') === '0')>Inactivos</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('products.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($products as $product)
            <article class="card overflow-hidden">
                <div class="relative h-40 bg-avocado-50">
                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    <span class="absolute left-3 top-3 badge {{ $product->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $product->is_active ? 'Activo' : 'Inactivo' }}</span>
                    <span class="absolute right-3 top-3 badge bg-white/90 badge-neutral">{{ $product->category?->name ?? 'Sin categoría' }}</span>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $product->sku }} · {{ $product->variety ?: 'Sin variedad' }}</p>
                    <h2 class="mt-1 font-display text-lg font-semibold text-ink">{{ $product->name }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $product->package_weight_kg }} kg · {{ ucfirst($product->unit) }} @if($product->is_organic)· Orgánico @endif</p>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <p class="text-slate-400">CLP</p>
                            <p class="font-semibold text-slate-800">{{ \App\Support\Money::format($product->prices->firstWhere('currency_code', 'CLP')?->price ?? $product->cost_clp, 'CLP') }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <p class="text-slate-400">Stock total</p>
                            <p class="font-semibold {{ $product->stocks->sum('quantity') <= $product->min_stock ? 'text-amber-700' : 'text-slate-800' }}">{{ rtrim(rtrim(number_format((float) $product->stocks->sum('quantity'), 3, ',', '.'), '0'), ',') }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                        <span class="text-[11px] text-slate-400">Costo {{ \App\Support\Money::format($product->cost_clp, 'CLP') }}</span>
                        @can('products.manage')
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary btn-sm">Editar</a>
                        @else
                            <span class="text-[11px] text-slate-400">Solo lectura</span>
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <div class="card sm:col-span-2 xl:col-span-3">
                <div class="card-body py-14 text-center text-sm text-slate-400">No hay productos con los filtros seleccionados.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $products->links() }}</div>
@endsection
