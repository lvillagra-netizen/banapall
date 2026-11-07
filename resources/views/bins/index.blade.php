@extends('layouts.app')

@section('title', 'BINS')
@section('eyebrow', 'Inventario · BINS')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Mantenedor de BINS (capacidad y costo).</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nuevo bin</h2></div>
            <form method="POST" action="{{ route('bins.store') }}" class="card-body space-y-3">
                @csrf
                <div>
                    <label class="label">Código *</label>
                    <input name="code" required value="{{ old('code') }}" class="input" placeholder="Ej. BIN-001">
                </div>
                <div>
                    <label class="label">Nombre *</label>
                    <input name="name" required value="{{ old('name') }}" class="input" placeholder="Ej. Bin 40kg">
                </div>
                <div>
                    <label class="label">Capacidad (kg)</label>
                    <input name="capacity_kg" type="number" step="0.001" value="{{ old('capacity_kg') }}" class="input">
                </div>
                <div>
                    <label class="label">Costo (CLP) *</label>
                    <input name="cost_clp" type="number" step="0.01" required value="{{ old('cost_clp') }}" class="input">
                </div>
                <button class="btn btn-primary w-full">Crear bin</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($bins as $bin)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $bin->name }}</h2>
                                    <span class="badge badge-neutral">{{ $bin->code }}</span>
                                    <span class="badge {{ $bin->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $bin->is_active ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Capacidad: {{ $bin->capacity_kg }} kg · Costo: {{ number_format((float)$bin->cost_clp, 0, ',', '.') }} CLP</p>
                            </div>
                            <div class="flex gap-2">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm cursor-pointer list-none">Editar</summary>
                                    <form method="POST" action="{{ route('bins.update', $bin) }}" class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <input name="code" required value="{{ $bin->code }}" class="input h-9 py-1 text-sm">
                                        <input name="name" required value="{{ $bin->name }}" class="input h-9 py-1 text-sm">
                                        <input name="capacity_kg" type="number" step="0.001" value="{{ $bin->capacity_kg }}" class="input h-9 py-1 text-sm">
                                        <input name="cost_clp" type="number" step="0.01" required value="{{ $bin->cost_clp }}" class="input h-9 py-1 text-sm">
                                        <label class="flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($bin->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                            Activo
                                        </label>
                                        <button class="btn btn-primary btn-sm w-full">Guardar</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('bins.destroy', $bin) }}" onsubmit="return confirm('¿Eliminar este bin?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay bins.</div></div>
            @endforelse
        </div>
    </div>
@endsection