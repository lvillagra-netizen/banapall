@extends('layouts.app')

@section('title', 'Cámaras')
@section('eyebrow', 'Inventario · Cámaras por bodega')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Cámaras asociadas a bodegas.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nueva cámara</h2></div>
            <form method="POST" action="{{ route('chambers.store') }}" class="card-body space-y-3">
                @csrf
                <div>
                    <label class="label">Bodega *</label>
                    <select name="warehouse_id" required class="input">
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Código *</label>
                    <input name="code" required value="{{ old('code') }}" class="input" placeholder="Ej. CAM-01">
                </div>
                <div>
                    <label class="label">Nombre *</label>
                    <input name="name" required value="{{ old('name') }}" class="input" placeholder="Ej. Cámara Frío 1">
                </div>
                <div>
                    <label class="label">Descripción</label>
                    <textarea name="description" rows="3" class="input">{{ old('description') }}</textarea>
                </div>
                <button class="btn btn-primary w-full">Crear cámara</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($chambers as $chamber)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $chamber->name }}</h2>
                                    <span class="badge badge-neutral">{{ $chamber->code }}</span>
                                    <span class="badge {{ $chamber->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $chamber->is_active ? 'Activa' : 'Inactiva' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Bodega: {{ $chamber->warehouse?->name }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $chamber->description ?: 'Sin descripción' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm cursor-pointer list-none">Editar</summary>
                                    <form method="POST" action="{{ route('chambers.update', $chamber) }}" class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <select name="warehouse_id" required class="input h-9 py-1 text-sm">
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" @selected($chamber->warehouse_id == $warehouse->id)>{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        <input name="code" required value="{{ $chamber->code }}" class="input h-9 py-1 text-sm">
                                        <input name="name" required value="{{ $chamber->name }}" class="input h-9 py-1 text-sm">
                                        <textarea name="description" rows="2" class="input text-sm">{{ $chamber->description }}</textarea>
                                        <label class="flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($chamber->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                            Activa
                                        </label>
                                        <button class="btn btn-primary btn-sm w-full">Guardar</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('chambers.destroy', $chamber) }}" onsubmit="return confirm('¿Eliminar esta cámara?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay cámaras.</div></div>
            @endforelse
        </div>
    </div>
@endsection