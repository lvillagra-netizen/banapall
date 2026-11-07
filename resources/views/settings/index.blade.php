@extends('layouts.app')

@section('title', 'Configuración')
@section('eyebrow', 'Sistema · Parámetros de operación')

@section('content')
    @php($groupLabels = [
        'empresa' => 'Empresa',
        'pos' => 'Punto de venta',
        'inventario' => 'Inventario',
        'ventas' => 'Ventas',
        'notificaciones' => 'Notificaciones',
        'sii' => 'SII',
        'general' => 'General',
    ])

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ($settings as $group => $items)
                    <div class="card">
                        <div class="card-header">
                            <h2 class="section-title">{{ $groupLabels[$group] ?? ucfirst($group) }}</h2>
                            <span class="badge badge-neutral">{{ $items->count() }} parámetros</span>
                        </div>
                        <div class="card-body grid gap-4 sm:grid-cols-2">
                            @foreach ($items as $setting)
                                <div class="{{ $setting->type === 'json' ? 'sm:col-span-2' : '' }}">
                                    <label class="label">{{ $setting->description ?: $setting->key }}</label>

                                    @if ($setting->type === 'boolean')
                                        <select name="settings[{{ $setting->key }}]" class="input">
                                            <option value="1" @selected($setting->value === '1' || $setting->value === 1)>Activado</option>
                                            <option value="0" @selected(! ($setting->value === '1' || $setting->value === 1))>Desactivado</option>
                                        </select>
                                    @elseif ($setting->key === 'pos.default_warehouse')
                                        <select name="settings[{{ $setting->key }}]" class="input">
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" @selected((string) $setting->value === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($setting->key === 'pos.default_currency')
                                        <select name="settings[{{ $setting->key }}]" class="input">
                                            @foreach (\App\Enums\Currency::cases() as $currency)
                                                <option value="{{ $currency->value }}" @selected($setting->value === $currency->value)>{{ $currency->value }} · {{ $currency->label() }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($setting->type === 'json')
                                        <textarea name="settings[{{ $setting->key }}]" rows="3" class="input">{{ is_array(json_decode((string) $setting->value, true)) ? json_encode(json_decode((string) $setting->value, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $setting->value }}</textarea>
                                    @else
                                        <input name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="input" @if($setting->type === 'integer' || $setting->type === 'decimal') type="number" step="{{ $setting->type === 'decimal' ? '0.01' : '1' }}" @endif>
                                    @endif

                                    <p class="mt-1 text-[10px] uppercase tracking-wide text-slate-400">{{ $setting->key }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button class="btn btn-primary">Guardar configuración</button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="section-title">Tipos de cambio</h2>
                        <p class="mt-1 text-xs text-slate-500">Referencia CLP por unidad de moneda.</p>
                    </div>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($rates as $currency => $rate)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $currency }}</p>
                                <p class="mt-1 text-lg font-semibold text-ink">{{ $rate ? '$'.number_format((float) $rate, 2, ',', '.') : '—' }}</p>
                                <p class="text-[10px] text-slate-400">CLP por unidad</p>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('settings.rates.store') }}" class="space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Moneda</label>
                                <select name="currency_code" class="input h-9 py-1 text-sm">
                                    <option value="USD">USD</option>
                                    <option value="PEN">PEN</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Valor CLP *</label>
                                <input type="number" name="rate_to_clp" step="0.0001" min="0.0001" required class="input h-9 py-1 text-sm text-right">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Fecha *</label>
                                <input type="date" name="effective_date" value="{{ now()->format('Y-m-d') }}" required class="input h-9 py-1 text-sm">
                            </div>
                            <div>
                                <label class="label">Fuente</label>
                                <input name="source" value="Manual" class="input h-9 py-1 text-sm">
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm w-full">Registrar tipo de cambio</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="section-title">Historial reciente</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Moneda</th><th class="text-right">CLP</th><th>Fuente</th></tr></thead>
                        <tbody>
                            @forelse ($rateHistory as $rate)
                                <tr>
                                    <td class="whitespace-nowrap text-xs">{{ $rate->effective_date?->format('d/m/Y') }}</td>
                                    <td><span class="badge badge-neutral">{{ $rate->currency_code }}</span></td>
                                    <td class="text-right text-xs font-medium">{{ number_format((float) $rate->rate_to_clp, 2, ',', '.') }}</td>
                                    <td class="text-xs text-slate-400">{{ $rate->source ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-xs text-slate-400">Sin registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="section-title">Bodegas</h2></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($warehouses as $warehouse)
                        <div class="flex items-center justify-between px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium text-slate-700">{{ $warehouse->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $warehouse->code }}</p>
                            </div>
                            <span class="badge {{ $warehouse->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $warehouse->is_active ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
