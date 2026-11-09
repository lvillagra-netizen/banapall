@extends('layouts.app')

@section('title', 'Categorías')
@section('eyebrow', 'Catálogo · Categorías de producto')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver a productos
            </a>
            <p class="mt-2 max-w-xl text-sm text-slate-500">Agrupa productos para el catálogo y el Módulo de Ventas.</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nueva categoría</h2></div>
            <form method="POST" action="{{ route('categories.store') }}" class="card-body space-y-3">
                @csrf
                <div>
                    <label class="label">Nombre *</label>
                    <input name="name" required value="{{ old('name') }}" class="input" placeholder="Ej. Paltas premium">
                </div>
                <div>
                    <label class="label">Descripción</label>
                    <textarea name="description" rows="3" class="input">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary w-full">Crear categoría</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($categories as $category)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $category->name }}</h2>
                                    <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $category->description ?: 'Sin descripción' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $category->products_count }} productos · slug {{ $category->slug }}</p>
                            </div>
                            <div class="flex gap-2">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm cursor-pointer list-none">Editar</summary>
                                    <form method="POST" action="{{ route('categories.update', $category) }}" class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" required value="{{ $category->name }}" class="input h-9 py-1 text-sm">
                                        <textarea name="description" rows="2" class="input text-sm">{{ $category->description }}</textarea>
                                        <label class="flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                            Activa
                                        </label>
                                        <button class="btn btn-primary btn-sm w-full">Guardar</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" @disabled($category->products_count > 0)>Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay categorías.</div></div>
            @endforelse
        </div>
    </div>
@endsection
