@extends('layouts.app')

@section('title', 'Bodegas')
@section('eyebrow', 'Inventario · Bodegas')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Mantenedor de bodegas (incluye Bodega Principal y Merma).</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nueva bodega</h2></div>
            <form method="POST" action="{{ route('warehouses.store') }}" class="card-body space-y-3">
                @csrf
                <div>
                    <label class="label">Código *</label>
                    <input name="code" required value="{{ old('code') }}" class="input" placeholder="Ej. BOD-01">
                </div>
                <div>
                    <label class="label">Nombre *</label>
                    <input name="name" required value="{{ old('name') }}" class="input" placeholder="Ej. Bodega Norte">
                </div>
                <div>
                    <label class="label">Dirección</label>
                    <input name="address" value="{{ old('address') }}" class="input">
                </div>
                <button class="btn btn-primary w-full">Crear bodega</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($warehouses as $warehouse)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $warehouse->name }}</h2>
                                    <span class="badge badge-neutral">{{ $warehouse->code }}</span>
                                    <span class="badge {{ $warehouse->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $warehouse->is_active ? 'Activa' : 'Inactiva' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $warehouse->address ?: 'Sin dirección' }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $warehouse->chambers_count }} cámaras</p>
                            </div>
                            <div class="flex gap-2">
                                <details class="group">
                                    <summary class="btn btn-secondary btn-sm cursor-pointer list-none">Editar</summary>
                                    <form method="POST" action="{{ route('warehouses.update', $warehouse) }}" class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <input name="code" required value="{{ $warehouse->code }}" class="input h-9 py-1 text-sm">
                                        <input name="name" required value="{{ $warehouse->name }}" class="input h-9 py-1 text-sm">
                                        <input name="address" value="{{ $warehouse->address }}" class="input h-9 py-1 text-sm">
                                        <label class="flex items-center gap-2 text-xs text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked($warehouse->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                            Activa
                                        </label>
                                        <button class="btn btn-primary btn-sm w-full">Guardar</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('warehouses.destroy', $warehouse) }}" onsubmit="return confirm('¿Eliminar esta bodega?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay bodegas.</div></div>
            @endforelse
        </div>
    </div>
@endsection