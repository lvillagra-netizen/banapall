@extends('layouts.app')

@section('title', 'Módulo de Ventas')
@section('eyebrow', 'Ventas · Módulo de Ventas')

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">No fue posible registrar la venta:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="posApp()" class="mx-auto w-full max-w-6xl space-y-5">
        @php $formAction = $editingSale ? route('pos.update', $editingSale['id']) : route('pos.store'); @endphp
    <form x-ref="posForm" method="POST" action="{{ $formAction }}" @submit="if (!canFinalize) { $event.preventDefault(); notify('Selecciona cliente y al menos un producto.', 'error'); }">
        @if ($editingSale)
            @method('PUT')
        @endif
        @csrf
            <input type="hidden" name="currency_code" :value="currency">
            <input type="hidden" name="tax_mode" :value="taxMode">
            <input type="hidden" name="warehouse_id" :value="warehouseId">
            <input type="hidden" name="mode" x-ref="modeInput" value="finalize">

            {{-- Sugerencia 1: barra pegajosa con Total + acciones --}}
            <div class="sticky top-4 z-40 mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-avocado-950 px-5 py-3 text-white shadow-[0_8px_20px_rgba(47,66,28,.25)]">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-avocado-100/70">Total a cobrar · <span x-text="currency"></span></p>
                    <p class="text-2xl font-extrabold leading-tight" x-text="formatMoney(total)"></p>
                    <p class="text-[11px] text-avocado-100/70">
                        <span x-text="selectedCustomer ? selectedCustomer.name : 'Sin cliente'"></span>
                        <template x-if="remaining > 0"> · <span x-text="'Pendiente ' + formatMoney(remaining)"></span></template>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="submitDraft()" class="btn border border-white/20 bg-white/10 text-white hover:bg-white/20" :disabled="cart.length === 0">Borrador</button>
                    <button type="button" @click="requestFinalize()" class="btn bg-avocado-300 text-avocado-950 hover:bg-avocado-200" :disabled="cart.length === 0">Validar y emitir</button>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
                {{-- Columna izquierda: Detalle --}}
                <div class="space-y-5">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="section-title">Detalle de la venta</h2>
                                <p class="mt-1 text-xs text-slate-500">Busca por nombre o SKU, ajusta cantidades o descuentos.</p>
                            </div>
                            <button type="button" @click="clearCart()" class="btn btn-ghost btn-sm text-rose-500" x-show="cart.length > 0" x-cloak>Limpiar</button>
                        </div>

                        <div class="card-body border-b border-slate-100">
                            {{-- Sugerencia 3: stock/precio junto al buscador --}}
                            <template x-if="entryProduct">
                                <div class="mb-3 flex flex-wrap gap-2 text-[11px]" x-cloak>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-avocado-50 px-2.5 py-1 font-semibold text-avocado-800">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="stockFor(entryProduct) > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        Stock: <span x-text="stockFor(entryProduct)"></span><template x-if="entryChamberId"><span class="text-[10px] text-slate-400"> (cámara)</span></template>
                                    </span>
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-600" x-text="'Precio Unitario ' + formatMoney(priceFor(entryProduct)) + ' / ' + entryProduct.unit"></span>
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-semibold text-slate-600" x-text="entryProduct.sku"></span>
                                </div>
                            </template>

                            <div class="space-y-3">
                                {{-- Fila 1: Producto y Cantidad --}}
                                <div class="grid gap-3 sm:grid-cols-[1fr_7rem]">
                                    <div class="relative" @click.outside="productOpen = false">
                                        <label class="label">Producto <span class="ml-1 font-normal normal-case text-slate-400">(↵ Enter agrega)</span></label>
                                        <input
                                            x-ref="productSearch"
                                            x-model="productQuery"
                                            type="text"
                                            class="input"
                                            placeholder="Buscar por nombre o SKU…"
                                            autocomplete="off"
                                            @focus="productOpen = true"
                                            @input="productOpen = true; entryProductId = ''"
                                            @keydown.enter.prevent="if (filteredProducts.length > 0) { selectProduct(filteredProducts[0]); } else { addEntry(); }"
                                            @keydown.escape="productOpen = false"
                                        >
                                        <div x-show="productOpen && filteredProducts.length > 0" x-cloak class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                            <template x-for="product in filteredProducts" :key="product.id">
                                                <button
                                                    type="button"
                                                    @click="selectProduct(product)"
                                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-left text-xs last:border-b-0 hover:bg-avocado-50"
                                                    :class="stockFor(product) <= 0 ? 'opacity-40' : ''"
                                                >
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-semibold text-slate-800" x-text="product.name"></span>
                                                        <span class="block truncate text-[10px] text-slate-400" x-text="product.sku + ' · ' + formatMoney(priceFor(product))"></span>
                                                    </span>
                                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-bold" :class="stockFor(product) > 0 ? 'bg-avocado-100 text-avocado-800' : 'bg-rose-100 text-rose-600'" x-text="'Stock ' + stockFor(product)"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div x-show="productOpen && productQuery && filteredProducts.length === 0" x-cloak class="absolute left-0 right-0 top-full z-30 mt-1 rounded-xl border border-slate-200 bg-white px-3 py-4 text-center text-xs text-slate-400 shadow-lg">
                                            Sin resultados para “<span x-text="productQuery"></span>”
                                        </div>
                                    </div>
                                    <div>
                                        <label class="label">Cantidad</label>
                                        <input x-ref="quantityInput" x-model.number="entryQuantity" type="number" min="1" step="1" class="input text-right" @focus="$event.target.select()" @keydown.enter.prevent="addEntry()">
                                    </div>
                                </div>

                                {{-- Fila 2: Opciones de ubicación (Cámara, Bin) y botón Agregar --}}
                                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                                    <div>
                                        <label class="label">Cámara (opcional)</label>
                                        <select x-model="entryChamberId" class="input text-sm">
                                            <option value="">Sin cámara</option>
                                            <template x-for="chamber in filteredChambers" :key="chamber.id">
                                                <option :value="chamber.id" x-text="chamber.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label">Bin (opcional)</label>
                                        <select x-model="entryBinId" class="input text-sm">
                                            <option value="">Sin bin</option>
                                            <template x-for="bin in filteredBins" :key="bin.id">
                                                <option :value="bin.id" x-text="bin.code + ' · ' + bin.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" @click="addEntry()" :disabled="!entryProductId || entryQuantity < 1" class="btn btn-primary h-10 w-full sm:w-32">Agregar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <template x-for="(item, index) in cart" :key="item.product_id">
                                <div class="px-5 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800" x-text="item.name"></p>
                                            <p class="text-[11px] text-slate-400" x-text="item.sku + ' · Precio Unitario ' + formatMoney(linePrice(item)) + ' / ' + item.unit"></p>
                                            <p x-show="item.bin_id" class="text-[10px] text-slate-500" x-text="'Bin: ' + bins.find(b => b.id === item.bin_id)?.code"></p>
                                            <p x-show="item.chamber_id" class="text-[10px] text-slate-500" x-text="'Cámara: ' + chambers.find(c => c.id === item.chamber_id)?.name"></p>
                                        </div>
                                        <button type="button" @click="removeItem(item)" class="text-xs font-medium text-rose-500 hover:text-rose-700">Quitar</button>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <div class="inline-flex items-center rounded-lg border border-slate-200">
                                            <button type="button" @click="decrement(item)" class="px-2.5 py-1.5 text-slate-500 hover:text-avocado-800">−</button>
                                            <span class="min-w-9 border-x border-slate-200 px-2 py-1.5 text-center text-sm font-semibold" x-text="item.quantity"></span>
                                            <button type="button" @click="increment(item)" class="px-2.5 py-1.5 text-slate-500 hover:text-avocado-800">+</button>
                                        </div>
                                        <label class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                            Desc.
                                            <input x-model.number="item.discount" type="number" min="0" step="1" class="input h-9 w-24 px-2 py-1 text-right text-xs">
                                        </label>
                                        <p class="ml-auto text-sm font-semibold text-ink" x-text="formatMoney(lineTaxable(item))"></p>
                                    </div>
                                </div>
                            </template>

                            <div x-show="cart.length === 0" class="px-5 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6h2l2.2 9.2a2 2 0 0 0 2 1.5h6.8a2 2 0 0 0 2-1.6L20 8H7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <p class="mt-3 text-sm text-slate-400">Busca productos por nombre o SKU para comenzar la venta.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Columna derecha: Cliente / Pagos / Notas --}}
                <div class="space-y-5">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="section-title">Cliente y condiciones</h2>
                        </div>
                        <div class="card-body space-y-4">
                            {{-- Sugerencia: buscador de clientes --}}
                            <div class="relative flex items-end gap-2" @click.outside="customerOpen = false">
                                <div class="relative min-w-0 flex-1">
                                    <label class="label">Cliente</label>
                                    <input
                                        x-model="customerQuery"
                                        type="text"
                                        class="input"
                                        placeholder="Buscar por nombre o RUT…"
                                        autocomplete="off"
                                        @focus="customerOpen = true"
                                        @input="customerOpen = true; customerId = ''"
                                        @keydown.escape="customerOpen = false"
                                    >
                                    <input type="hidden" name="customer_id" :value="customerId">
                                    <div x-show="customerOpen && filteredCustomers.length > 0" x-cloak class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                                        <template x-for="customer in filteredCustomers" :key="customer.id">
                                            <button
                                                type="button"
                                                @click="selectCustomer(customer)"
                                                class="block w-full border-b border-slate-100 px-3 py-2 text-left text-xs last:border-b-0 hover:bg-avocado-50"
                                            >
                                                <span class="block truncate font-semibold text-slate-800" x-text="customer.name"></span>
                                                <span class="block truncate text-[10px] text-slate-400" x-text="customer.tax_id + ' · ' + (customer.city || '') + ' · ' + customer.currency_code"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <div x-show="customerOpen && customerQuery && filteredCustomers.length === 0" x-cloak class="absolute left-0 right-0 top-full z-30 mt-1 rounded-xl border border-slate-200 bg-white px-3 py-4 text-center text-xs text-slate-400 shadow-lg">
                                        Sin resultados para “<span x-text="customerQuery"></span>”
                                    </div>
                                </div>
                                <a href="{{ route('customers.create') }}" class="btn btn-secondary btn-sm shrink-0">Nuevo</a>
                            </div>

                            <div x-show="selectedCustomer" x-cloak class="rounded-xl border border-avocado-100 bg-avocado-50/70 p-3 text-xs">
                                <p class="font-semibold text-avocado-900" x-text="selectedCustomer?.name"></p>
                                <p class="mt-0.5 text-avocado-800/70" x-text="selectedCustomer?.tax_id + ' · ' + (selectedCustomer?.city || 'Sin ciudad')"></p>
                                <p class="mt-0.5 text-avocado-800/70" x-text="'País ' + selectedCustomer?.country_code + ' · Moneda sugerida ' + selectedCustomer?.currency_code"></p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label">Moneda</label>
                                    <select x-model="currency" disabled class="input cursor-not-allowed opacity-70">
                                        <template x-for="(meta, code) in currencies" :key="code">
                                            <option :value="code" x-text="code + ' · ' + meta.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Impuesto</label>
                                    <select x-model="taxMode" disabled class="input cursor-not-allowed opacity-70">
                                        <template x-for="tax in taxes" :key="tax.value">
                                            <option :value="tax.value" x-text="tax.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="section-title">Pagos</h2>
                                <p class="mt-1 text-xs text-slate-400">Puedes dejar saldo pendiente.</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="addPayment()" class="btn btn-ghost btn-sm">+ Pago</button>
                                <button type="button" @click="removeLastPayment()" class="btn btn-ghost btn-sm" :disabled="payments.length === 0">− Pago</button>
                            </div>
                        </div>
                        <div class="card-body space-y-3">
                            <template x-for="(payment, index) in payments" :key="index">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select :name="'payments[' + index + '][method]'" x-model="payment.method" @change="onMethodChange(payment)" class="input h-9 py-1 text-xs" :class="!payment.method && Number(payment.amount) > 0 ? 'border-rose-400 bg-rose-50/50' : ''">
                                            <option value="">Seleccione el tipo de Pago...</option>
                                            <template x-for="method in paymentMethods" :key="method.value">
                                                <option :value="method.value" x-text="method.label"></option>
                                            </template>
                                        </select>
                                        <select x-model="payment.currency_code" disabled class="input h-9 cursor-not-allowed py-1 text-xs opacity-70">
                                            <option :value="payment.currency_code" x-text="payment.currency_code"></option>
                                        </select>
                                        <input type="hidden" :name="'payments[' + index + '][currency_code]'" :value="payment.currency_code">
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input :name="'payments[' + index + '][amount]'" x-model="payment.amount" type="number" min="0" step="0.01" class="input h-9 py-1 text-right text-xs" placeholder="Monto" @focus="$event.target.select()">
                                        <button type="button" @click="fillRemaining(payment)" class="btn btn-secondary btn-sm shrink-0">Saldo</button>
                                        <button type="button" @click="removePayment(index)" class="btn btn-ghost btn-sm shrink-0 px-2 text-rose-500">×</button>
                                    </div>
                                    <input :name="'payments[' + index + '][reference]'" x-model="payment.reference" class="input mt-2 h-9 py-1 text-xs" placeholder="Referencia / operación (opcional)">
                                </div>
                            </template>

                            <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                                <span class="text-slate-500">Pagado equivalente</span>
                                <span class="font-semibold text-slate-700" x-text="formatMoney(paidTotal)"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Saldo pendiente</span>
                                <span class="font-semibold" :class="remaining > 0 ? 'text-rose-600' : 'text-avocado-700'" x-text="formatMoney(remaining)"></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body space-y-3">
                            {{-- Sugerencia 5: Notas colapsable --}}
                            <details class="group">
                                <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-slate-500 select-none">
                                    Notas (opcional)
                                    <span class="transition group-open:rotate-180">▾</span>
                                </summary>
                                <textarea name="notes" x-model="notes" rows="2" class="input mt-2" placeholder="Observaciones para el documento…"></textarea>
                            </details>

                            <div class="space-y-1.5 rounded-xl bg-slate-50 p-4 text-sm">
                                <div class="flex justify-between text-slate-500"><span>Neto</span><span x-text="formatMoney(subtotal)"></span></div>
                                <div class="flex justify-between text-slate-500" x-show="discountTotal > 0" x-cloak><span>Descuentos</span><span x-text="'− ' + formatMoney(discountTotal)"></span></div>
                                <div class="flex justify-between text-slate-500"><span x-text="'Impuesto (' + (taxRate * 100) + '%)'"></span><span x-text="formatMoney(taxTotal)"></span></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-ink"><span>Total</span><span x-text="formatMoney(total)"></span></div>
                            </div>

                            <div x-show="flash" x-cloak class="rounded-lg px-3 py-2 text-xs" :class="flashType === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700'" x-text="flash"></div>

                            <p class="text-center text-[10px] leading-relaxed text-slate-400">Al validar se descuenta stock, se emite la nota de venta y se transmiten los registros simulados al SII y contabilidad. Usa la barra superior para emitir desde cualquier punto de la página.</p>
                        </div>
                    </div>
                </div>
            </div>

            <template x-for="(item, index) in cart" :key="'hidden-' + item.product_id + '-' + (item.bin_id || '')">
                <div>
                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                    <input type="hidden" :name="'items[' + index + '][chamber_id]'" :value="item.chamber_id ?? ''">
                    <input type="hidden" :name="'items[' + index + '][bin_id]'" :value="item.bin_id ?? ''">
                    <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                    <input type="hidden" :name="'items[' + index + '][discount_amount]'" :value="item.discount || 0">
                </div>
            </template>
        </form>

        {{-- Modal de alerta de stock --}}
        <div x-show="stockAlertOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="stockAlertOpen = false" @keydown.escape.window="stockAlertOpen = false">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Stock insuficiente</h3>
                        <p class="mt-1 text-xs text-slate-500">Revisa la cantidad ingresada.</p>
                    </div>
                </div>

                <p class="rounded-xl bg-rose-50 px-3 py-2.5 text-sm font-semibold text-rose-700" x-text="stockAlertMessage"></p>

                <div class="flex justify-end">
                    <button type="button" @click="stockAlertOpen = false" class="btn btn-primary text-xs">Entendido</button>
                </div>
            </div>
        </div>

        {{-- Modal de confirmación para Validar y Emitir --}}
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="confirmOpen = false" @keydown.escape.window="confirmOpen = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-avocado-100 text-avocado-800">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Confirmar emisión de venta</h3>
                        <p class="text-xs text-slate-500">¿Estás seguro de validar y emitir esta venta?</p>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-xs space-y-1.5 text-slate-600">
                    <div class="flex justify-between">
                        <span>Cliente:</span>
                        <strong class="text-slate-800" x-text="selectedCustomer ? selectedCustomer.name : '—'"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Total:</span>
                        <strong class="text-slate-800" x-text="formatMoney(total)"></strong>
                    </div>
                    <div class="flex justify-between" x-show="paidTotal > 0">
                        <span>Monto abonado:</span>
                        <strong class="text-slate-800" x-text="formatMoney(paidTotal)"></strong>
                    </div>
                    <div class="flex justify-between" x-show="remaining > 0">
                        <span>Saldo pendiente:</span>
                        <strong class="text-rose-600" x-text="formatMoney(remaining)"></strong>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400">Al confirmar se descontará el stock de bodega, se generará la nota de venta y el registro en el sistema.</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="confirmOpen = false" class="btn btn-secondary text-xs">Cancelar</button>
                    <button type="button" @click="confirmFinalize()" class="btn btn-primary text-xs">Confirmar y emitir</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.__POS_CONFIG__ = {
            products: @json($products),
            customers: @json($customers),
            warehouses: @json($warehouses->map->only(['id', 'name'])->values()),
            chambers: @json($chambers->values()),
            bins: @json($bins->values()),
            currencies: @json($currencies),
            rates: @json($rates),
            taxes: @json($taxes),
            paymentMethods: @json($paymentMethods),
            defaultWarehouse: @json($defaultWarehouse),
            defaultCurrency: @json($defaultCurrency),
            editingSale: @json($editingSale),
        };
    </script>
@endpush
