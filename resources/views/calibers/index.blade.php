@extends('layouts.app')

@section('title', 'Calibres')
@section('eyebrow', 'Catálogo · Calibres de palta')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver a productos
            </a>
            <p class="mt-2 max-w-xl text-sm text-slate-500">Clasificación de paltas por calibre.</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nuevo calibre</h2></div>
            <form method="POST" action="{{ route('calibers.store') }}" class="card-body space-y-3">
                @csrf
                <div>
                    <label class="label">Nombre *</label>
                    <input name="name" required value="{{ old('name') }}" class="input" placeholder="Ej. Calibre 1">
                </div>
                <div>
                    <label class="label">Descripción</label>
                    <textarea name="description" rows="3" class="input">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary w-full">Crear calibre</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($calibers as $caliber)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $caliber->name }}</h2>
                                    <span class="badge {{ $caliber->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $caliber->is_active ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $caliber->description ?: 'Sin descripción' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $caliber->products_count }} productos · slug {{ $caliber->slug }}</p>
                            </div>
                            <div class="flex gap-2">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm cursor-pointer list-none">Editar</summary>
                                    <form method="POST" action="{{ route('calibers.update', $caliber) }}" class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" required value="{{ $caliber->name }}" class="input h-9 py-1 text-sm">
                                        <textarea name="description" rows="2" class="input text-sm">{{ $caliber->description }}</textarea>
                                        <label class="flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($caliber->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                            Activo
                                        </label>
                                        <button class="btn btn-primary btn-sm w-full">Guardar</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('calibers.destroy', $caliber) }}" onsubmit="return confirm('¿Eliminar este calibre?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" @disabled($caliber->products_count > 0)>Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay calibres.</div></div>
            @endforelse
        </div>
    </div>
@endsection