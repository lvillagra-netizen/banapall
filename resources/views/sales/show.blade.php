@extends('layouts.app')

@section('title', $sale->number ? 'Venta '.$sale->number : 'Borrador #'.$sale->id)
@section('eyebrow', 'Ventas · Detalle del documento')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('sales.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver a ventas
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="font-display text-3xl font-semibold text-ink">{{ $sale->number ?: 'Borrador #'.$sale->id }}</h1>
                <span class="badge {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span>
                <span class="badge badge-neutral">{{ $sale->currency_code }}</span>
                @if ($sale->paymentStatus()->value !== 'pendiente')
                    <span class="badge {{ $sale->paymentStatus()->badge() }}">{{ $sale->paymentStatus()->label() }}</span>
                @endif
            </div>
            <p class="mt-1.5 text-sm text-slate-500">Emitida el {{ $sale->created_at->format('d/m/Y') }} a las {{ $sale->created_at->format('H:i') }} por {{ $sale->seller?->name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($document)
                <a href="{{ route('billing.documents.pdf', $document) }}" class="btn btn-secondary" target="_blank" rel="noopener">Descargar PDF</a>
            @endif

            @if ($sale->isDraft() && auth()->user()->can('sales.validate'))
                <form method="POST" action="{{ route('sales.validate', $sale) }}" onsubmit="return confirm('¿Validar la venta? Se descontará stock y se emitirá la nota de venta.');">
                    @csrf
                    <button class="btn btn-primary">Validar venta</button>
                </form>
            @endif
            @if ($sale->isDraft())
                <a href="{{ route('pos.edit', $sale) }}" class="btn btn-primary ml-2">Editar borrador</a>
            @endif

            @if (in_array($sale->status->value, ['validada', 'completada'], true) && auth()->user()->can('sales.cancel'))
                <button type="button" onclick="document.getElementById('cancel-sale').showModal()" class="btn btn-danger">Anular</button>
            @endif
        </div>
    </div>

    @if ($sale->isCancelled())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Venta anulada el {{ $sale->cancelled_at?->format('d/m/Y H:i') }} por {{ $sale->canceller?->name }}</p>
            <p class="mt-1">Motivo: {{ $sale->cancellation_reason }}</p>
            <p class="mt-1 text-xs text-rose-600">El stock y los asientos contables fueron reversados. Los pagos quedaron marcados como anulados.</p>
        </div>
    @endif

    @if ($sale->isDraft() && $sale->validation_errors)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-semibold">La última validación encontró observaciones:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                @foreach ($sale->validation_errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.55fr_1fr]">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="section-title">Productos</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $sale->items->count() }} líneas · bodega {{ $sale->warehouse?->name }}</p>
                    </div>
                    <span class="badge badge-neutral">{{ \App\Enums\TaxMode::tryFrom($sale->tax_mode->value)?->label() }}</span>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio Unitario</th>
                                <th class="text-right">Desc.</th>
                                <th class="text-right">Impuesto</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium text-slate-800">{{ $item->product_name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $item->sku }}</p>
                                    </td>
                                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', '.'), '0'), ',') }}</td>
                                    <td class="whitespace-nowrap text-right">{{ \App\Support\Money::format($item->unit_price, $sale->currency_code) }}</td>
                                    <td class="text-right text-slate-400">{{ (float) $item->discount_amount > 0 ? \App\Support\Money::format($item->discount_amount, $sale->currency_code) : '—' }}</td>
                                    <td class="text-right text-slate-400">{{ \App\Support\Money::format($item->tax_amount, $sale->currency_code) }}</td>
                                    <td class="whitespace-nowrap text-right font-semibold text-ink">{{ \App\Support\Money::format(\App\Support\Money::subtractCurrency($sale->currency_code, $item->line_subtotal, $item->discount_amount), $sale->currency_code) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-5">
                    <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-500"><dt>Neto</dt><dd>{{ \App\Support\Money::format($sale->subtotal, $sale->currency_code) }}</dd></div>
                        <div class="flex justify-between text-slate-500"><dt>Descuentos</dt><dd>{{ \App\Support\Money::format($sale->discount_total, $sale->currency_code) }}</dd></div>
                        <div class="flex justify-between text-slate-500"><dt>{{ $sale->tax_mode->label() }} ({{ $sale->tax_mode->percentage() }}%)</dt><dd>{{ \App\Support\Money::format($sale->tax_total, $sale->currency_code) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-ink"><dt>Total</dt><dd>{{ \App\Support\Money::format($sale->total, $sale->currency_code) }}</dd></div>
                        <div class="flex justify-between text-slate-500"><dt>Pagado</dt><dd>{{ \App\Support\Money::format($sale->paid_total, $sale->currency_code) }}</dd></div>
                        <div class="flex justify-between font-semibold {{ (float) $sale->balance > 0 ? 'text-rose-600' : 'text-avocado-700' }}"><dt>Saldo</dt><dd>{{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="section-title">Pagos</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($sale->isDraft())
                                Pagos registrados en el borrador · pendientes de confirmar al validar
                            @else
                                Pagos confirmados y anulados
                            @endif
                        </p>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Medio</th>
                                <th>Referencia</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Equivalente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sale->payments as $payment)
                                <tr>
                                    <td class="whitespace-nowrap text-xs">{{ ($payment->paid_at ?? $payment->created_at)?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $payment->method->label() }}</td>
                                    <td class="text-xs text-slate-400">{{ $payment->reference ?: '—' }}</td>
                                    <td class="whitespace-nowrap text-right font-medium">{{ \App\Support\Money::format($payment->amount, $payment->currency_code) }}</td>
                                    <td class="whitespace-nowrap text-right text-slate-500">{{ \App\Support\Money::format($payment->amount_in_sale_currency, $sale->currency_code) }}</td>
                                    <td>
                                        @php($paymentBadge = match ($payment->status) { 'confirmado' => 'badge-success', 'anulado' => 'badge-danger', default => 'badge-warning' })
                                        <span class="badge {{ $paymentBadge }}">{{ ucfirst($payment->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-center text-sm text-slate-400">Aún no hay pagos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Cliente</h2></div>
                <div class="card-body space-y-3 text-sm">
                    <div>
                        <p class="font-semibold text-slate-800">{{ $sale->customer?->name }}</p>
                        <p class="text-xs text-slate-400">{{ $sale->customer?->displayTaxId() }}</p>
                    </div>
                    <div class="space-y-1 text-xs text-slate-500">
                        <p>{{ $sale->customer?->email ?: 'Sin email' }}</p>
                        <p>{{ $sale->customer?->phone ?: 'Sin teléfono' }}</p>
                        <p>{{ collect([$sale->customer?->address, $sale->customer?->city, $sale->customer?->country_code])->filter()->implode(', ') ?: 'Sin dirección' }}</p>
                    </div>
                    @if ($sale->customer)
                        <a href="{{ route('customers.show', $sale->customer) }}" class="btn btn-secondary btn-sm w-full">Ver ficha del cliente</a>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="section-title">Documento</h2></div>
                <div class="card-body space-y-3 text-sm">
                    @if ($document)
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-800">{{ $document->type->label() }} {{ $document->folio }}</p>
                                <p class="text-xs text-slate-400">Emitido {{ $document->issued_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="badge {{ $document->status->badge() }}">{{ $document->status->label() }}</span>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">SII simulado</span>
                                <span class="badge {{ $document->sii_status->badge() }}">{{ $document->sii_status->label() }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-slate-500">Contabilidad</span>
                                <span class="font-medium text-slate-700">{{ ucfirst((string) $document->accounting_status) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('billing.documents.pdf', $document) }}" class="btn btn-secondary btn-sm w-full" target="_blank" rel="noopener">Abrir nota de venta</a>
                    @else
                        <p class="text-xs text-slate-400">El documento se emite al validar la venta. Los borradores no consumen folio.</p>
                    @endif
                </div>
            </div>

            @if ((float) $sale->balance > 0 && in_array($sale->status->value, ['validada', 'completada'], true) && auth()->user()->can('sales.payment'))
                <div class="card">
                    <div class="card-header"><h2 class="section-title">Registrar pago</h2></div>
                    <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="card-body space-y-3">
                        @csrf
                        <div>
                            <label class="label">Medio de pago</label>
                            <select name="method" class="input" required>
                                <option value="" disabled selected>Seleccione el tipo de Pago...</option>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Moneda</label>
                                <select name="currency_code" class="input">
                                    @foreach (\App\Enums\Currency::cases() as $currency)
                                        <option value="{{ $currency->value }}" @selected($currency->value === $sale->currency_code)>{{ $currency->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Monto</label>
                                <input name="amount" type="number" step="0.01" min="0.01" class="input text-right" value="{{ $sale->balance }}">
                            </div>
                        </div>
                        <div>
                            <label class="label">Referencia</label>
                            <input name="reference" class="input" placeholder="N° operación (opcional)">
                        </div>
                        <button class="btn btn-primary w-full">Registrar pago</button>
                        <p class="text-[10px] text-slate-400">Saldo pendiente: {{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</p>
                    </form>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="section-title">Trazabilidad</h2></div>
                <div class="card-body space-y-3 text-xs">
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Bodega</span><span class="font-medium text-slate-700">{{ $sale->warehouse?->name }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Tipo de cambio</span><span class="font-medium text-slate-700">{{ number_format((float) $sale->exchange_rate, 4, ',', '.') }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Creada</span><span class="font-medium text-slate-700">{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Validada</span><span class="font-medium text-slate-700">{{ $sale->validated_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Validador</span><span class="font-medium text-slate-700">{{ $sale->validator?->name ?? '—' }}</span></div>
                    @if ($sale->notes)
                        <div class="border-t border-slate-100 pt-3"><p class="text-slate-400">Notas</p><p class="mt-1 text-slate-600">{{ $sale->notes }}</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (in_array($sale->status->value, ['validada', 'completada'], true) && auth()->user()->can('sales.cancel'))
        <dialog id="cancel-sale" class="w-full max-w-md rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-900/40">
            <form method="POST" action="{{ route('sales.cancel', $sale) }}">
                @csrf
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="font-display text-lg font-semibold text-ink">Anular venta {{ $sale->number }}</h3>
                    <p class="mt-1 text-xs text-slate-500">Se repondrá el stock, se revertirá la contabilidad y se anulará el documento.</p>
                </div>
                <div class="p-5">
                    <label class="label">Motivo de anulación</label>
                    <textarea name="reason" rows="3" required minlength="5" class="input" placeholder="Describe el motivo…"></textarea>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <button type="button" onclick="document.getElementById('cancel-sale').close()" class="btn btn-secondary">Cancelar</button>
                    <button class="btn btn-danger">Confirmar anulación</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
