@extends('layouts.app')

@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')
@section('eyebrow', 'Catálogo · ' . ($product->exists ? 'Editar producto' : 'Nuevo producto'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver a productos
        </a>
    </div>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Información del producto</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Nombre *</label>
                        <input name="name" required value="{{ old('name', $product->name) }}" class="input" placeholder="Palta Hass orgánica">
                    </div>
                    <div>
                        <label class="label">SKU *</label>
                        <input name="sku" required value="{{ old('sku', $product->sku) }}" class="input uppercase" placeholder="PAL-HASS-10">
                    </div>
                    <div>
                        <label class="label">Categoría</label>
                        <select name="product_category_id" class="input">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('product_category_id', $product->product_category_id) === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Variedad</label>
                        <input name="variety" value="{{ old('variety', $product->variety) }}" class="input" placeholder="Hass, Fuerte, Edranol…">
                    </div>
                    <div>
                        <label class="label">Unidad de venta *</label>
                        <input name="unit" required value="{{ old('unit', $product->unit) }}" class="input" placeholder="caja, bin, kilo">
                    </div>
                    <div>
                        <label class="label">Peso por envase (kg)</label>
                        <input type="number" name="package_weight_kg" min="0" step="0.01" value="{{ old('package_weight_kg', $product->package_weight_kg) }}" class="input text-right">
                    </div>
                    <div>
                        <label class="label">Stock mínimo</label>
                        <input type="number" name="min_stock" min="0" step="0.001" value="{{ old('min_stock', $product->min_stock) }}" class="input text-right">
                    </div>
                    <div>
                        <label class="label">Costo base CLP *</label>
                        <input type="number" name="cost_clp" required min="0" step="0.01" value="{{ old('cost_clp', $product->cost_clp) }}" class="input text-right" placeholder="0">
                    </div>
                    <div>
                        <label class="label">Certificador</label>
                        <input name="certifier" value="{{ old('certifier', $product->certifier) }}" class="input" placeholder="Ecocert, CERES…">
                    </div>
                    <div>
                        <label class="label">Código de certificación</label>
                        <input name="certification_code" value="{{ old('certification_code', $product->certification_code) }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Descripción</label>
                        <textarea name="description" rows="3" class="input" placeholder="Descripción comercial del producto…">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_organic" value="1" @checked(old('is_organic', $product->is_organic ?? true)) class="h-4 w-4 rounded border-slate-300 text-avocado-700 focus:ring-avocado-500">
                        Producto orgánico certificado
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-avocado-700 focus:ring-avocado-500">
                        Activo para venta
                    </label>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="section-title">Precios por moneda</h2>
                        <p class="mt-1 text-xs text-slate-500">CLP obligatorio. USD y PEN se usan al vender en esas monedas.</p>
                    </div>
                </div>
                <div class="card-body grid gap-4 sm:grid-cols-3">
                    @foreach ($currencies as $code => $meta)
                        <div>
                            <label class="label">{{ $code }} · {{ $meta['label'] }}</label>
                            <input type="number" name="prices[{{ $code }}]" min="0" step="{{ $code === 'CLP' ? '1' : '0.01' }}" value="{{ old('prices.'.$code, $prices[$code] ?? null) }}" class="input text-right" @required($code === 'CLP')>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Imagen</h2></div>
                <div class="card-body space-y-3">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img src="{{ $product->exists ? $product->imageUrl() : asset('images/products/hass.svg') }}" alt="Imagen del producto" class="h-44 w-full object-cover">
                    </div>
                    <input type="file" name="image" accept="image/*" class="input file:mr-3 file:rounded-lg file:border-0 file:bg-avocado-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-avocado-800">
                    <p class="text-[11px] text-slate-400">JPG, PNG o WebP hasta 4 MB. Reemplaza la imagen anterior.</p>
                </div>
            </div>

            @if ($product->exists)
                <div class="card">
                    <div class="card-header"><h2 class="section-title">Stock por bodega</h2></div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($product->stocks as $stock)
                            <div class="flex items-center justify-between px-5 py-3 text-sm">
                                <span class="text-slate-500">{{ $stock->warehouse?->name }}</span>
                                <span class="font-semibold text-slate-800">{{ rtrim(rtrim(number_format((float) $stock->quantity, 3, ',', '.'), '0'), ',') }}</span>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-center text-xs text-slate-400">Sin stock registrado. Usa Inventario para ingresar unidades.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body space-y-2">
                    <button class="btn btn-primary w-full">{{ $product->exists ? 'Guardar cambios' : 'Crear producto' }}</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary w-full">Cancelar</a>
                </div>
            </div>
        </div>
    </form>

    @if ($product->exists)
        <div class="mt-6 card border-rose-200">
            <div class="card-body flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-rose-700">Eliminar producto</p>
                    <p class="mt-1 text-xs text-slate-500">Si el producto tiene ventas, se desactivará para conservar el historial.</p>
                </div>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('¿Eliminar este producto?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">Eliminar producto</button>
                </form>
            </div>
        </div>
    @endif
@endsection
