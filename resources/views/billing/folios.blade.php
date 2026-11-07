@extends('layouts.app')

@section('title', 'Folios')
@section('eyebrow', 'Administración · Folios y correlativos')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Cada documento consume un folio correlativo. Si un rango se agota, la validación de venta se detiene.</p>
        <a href="{{ route('billing.documents') }}" class="btn btn-secondary">Ver documentos</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="metric-label">{{ $stat['type']->label() }}</p>
                        <p class="mt-2 text-3xl font-semibold {{ $stat['available'] > 0 ? 'text-ink' : 'text-rose-600' }}">{{ number_format($stat['available'], 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-slate-400">folios disponibles · {{ $stat['ranges']->count() }} rangos</p>
                    </div>
                    <span class="badge {{ $stat['available'] > 0 ? 'badge-success' : 'badge-danger' }}">{{ $stat['type']->prefix() }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_2fr]">
        <div class="card h-fit">
            <div class="card-header"><h2 class="section-title">Nuevo rango</h2></div>
            <form method="POST" action="{{ route('billing.folios.store') }}" class="card-body grid gap-3 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label class="label">Tipo de documento *</label>
                    <select name="document_type" class="input">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }} ({{ $type->prefix() }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Prefijo *</label>
                    <input name="prefix" required value="{{ old('prefix') }}" class="input uppercase" placeholder="NV">
                </div>
                <div>
                    <label class="label">Desde *</label>
                    <input type="number" name="start_number" required min="1" value="{{ old('start_number', 1) }}" class="input text-right">
                </div>
                <div>
                    <label class="label">Hasta *</label>
                    <input type="number" name="end_number" required min="1" value="{{ old('end_number') }}" class="input text-right">
                </div>
                <div>
                    <label class="label">Próximo *</label>
                    <input type="number" name="next_number" required min="1" value="{{ old('next_number', 1) }}" class="input text-right">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Notas</label>
                    <input name="notes" value="{{ old('notes') }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <button class="btn btn-primary w-full">Crear rango</button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($ranges as $range)
                <div class="card">
                    <div class="card-body">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-display text-lg font-semibold text-ink">{{ $range->document_type->label() }} · {{ $range->prefix }}</h2>
                                    <span class="badge {{ $range->hasAvailability() ? 'badge-success' : 'badge-danger' }}">{{ $range->hasAvailability() ? 'Disponible' : 'Agotado / inactivo' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Rango {{ $range->prefix }}-{{ str_pad($range->start_number, 6, '0', STR_PAD_LEFT) }} al {{ $range->prefix }}-{{ str_pad($range->end_number, 6, '0', STR_PAD_LEFT) }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">Próximo {{ $range->formattedNext() }} · {{ number_format($range->remaining(), 0, ',', '.') }} restantes</p>
                            </div>
                            <span class="badge badge-neutral">{{ $range->is_active ? 'Activo' : 'Inactivo' }}</span>
                        </div>

                        <form method="POST" action="{{ route('billing.folios.update', $range) }}" class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="label">Hasta</label>
                                <input type="number" name="end_number" min="{{ $range->next_number }}" value="{{ $range->end_number }}" class="input h-9 py-1 text-sm text-right">
                            </div>
                            <div>
                                <label class="label">Próximo</label>
                                <input type="number" name="next_number" min="{{ $range->start_number }}" max="{{ $range->end_number }}" value="{{ $range->next_number }}" class="input h-9 py-1 text-sm text-right">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Notas</label>
                                <input name="notes" value="{{ $range->notes }}" class="input h-9 py-1 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="is_active" value="1" @checked($range->is_active) class="h-4 w-4 rounded border-slate-300 text-avocado-700">
                                Rango activo
                            </label>
                            <div class="flex justify-end">
                                <button class="btn btn-primary btn-sm">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body py-14 text-center text-sm text-slate-400">Aún no hay rangos de folios configurados.</div></div>
            @endforelse
        </div>
    </div>
@endsection
