@extends('layouts.app')

@section('title', $customer->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('eyebrow', 'Catálogo · ' . ($customer->exists ? 'Editar ficha' : 'Nueva ficha'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver a clientes
        </a>
    </div>

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
          x-data="{
              country: '{{ old('country_code', $customer->country_code) }}',
              currency: '{{ old('currency_code', $customer->currency_code) }}',
              tax: '{{ old('tax_id_type', $customer->tax_id_type) }}',
              syncCountry() {
                  this.currency = this.country === 'CL' ? 'CLP' : (this.country === 'PE' ? 'PEN' : 'USD');
                  this.tax = this.country === 'CL' ? 'RUT' : (this.country === 'PE' ? 'RUC' : 'OTHER');
              }
          }"
          class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        @csrf
        @if ($customer->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Datos del cliente</h2>
                    <p class="mt-1 text-xs text-slate-500">La moneda y el impuesto se usan como sugerencia al vender.</p>
                </div>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Nombre comercial *</label>
                    <input name="name" required value="{{ old('name', $customer->name) }}" class="input" placeholder="Ej. Distribuidora Andina SpA">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Razón social</label>
                    <input name="legal_name" value="{{ old('legal_name', $customer->legal_name) }}" class="input" placeholder="Nombre legal para facturación">
                </div>
                <div>
                    <label class="label">País *</label>
                    <select name="country_code" x-model="country" @change="syncCountry()" class="input">
                        @foreach ($countries as $code => $country)
                            <option value="{{ $code }}">{{ $country['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Tipo de documento *</label>
                    <select name="tax_id_type" x-model="tax" class="input">
                        <option value="RUT">RUT (Chile)</option>
                        <option value="RUC">RUC (Perú)</option>
                        <option value="OTHER">Otro / Tax ID</option>
                    </select>
                </div>
                <div>
                    <label class="label">Número de documento</label>
                    <input name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}" class="input" placeholder="76.123.456-7">
                </div>
                <div>
                    <label class="label">Moneda de venta *</label>
                    <select name="currency_code" x-model="currency" class="input">
                        @foreach ($currencies as $code => $meta)
                            <option value="{{ $code }}">{{ $code }} · {{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="input" placeholder="contacto@empresa.cl">
                </div>
                <div>
                    <label class="label">Teléfono</label>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" class="input" placeholder="+56 9 1234 5678">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Dirección</label>
                    <input name="address" value="{{ old('address', $customer->address) }}" class="input" placeholder="Calle, número, oficina">
                </div>
                <div>
                    <label class="label">Ciudad</label>
                    <input name="city" value="{{ old('city', $customer->city) }}" class="input">
                </div>
                <div>
                    <label class="label">Región / estado</label>
                    <input name="region" value="{{ old('region', $customer->region) }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Notas internas</label>
                    <textarea name="notes" rows="3" class="input" placeholder="Condiciones comerciales, contacto de pagos, etc.">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Condiciones</h2></div>
                <div class="card-body space-y-4">
                    <div>
                        <label class="label">Límite de crédito</label>
                        <input type="number" name="credit_limit" min="0" step="0.01" value="{{ old('credit_limit', $customer->credit_limit) }}" class="input text-right" placeholder="0">
                        <p class="mt-1 text-[11px] text-slate-400">En la moneda de venta del cliente.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-avocado-700 focus:ring-avocado-500">
                        Cliente activo para ventas
                    </label>
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-2">
                    <button class="btn btn-primary w-full">{{ $customer->exists ? 'Guardar cambios' : 'Crear cliente' }}</button>
                    <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-secondary w-full">Cancelar</a>
                </div>
            </div>

        </div>
    </form>

    @if ($customer->exists)
        <div class="mt-6 card border-rose-200">
            <div class="card-body flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-rose-700">Eliminar cliente</p>
                    <p class="mt-1 text-xs text-slate-500">Sus ventas históricas se conservan. La ficha quedará inactiva.</p>
                </div>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('¿Eliminar este cliente?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">Eliminar cliente</button>
                </form>
            </div>
        </div>
    @endif
@endsection
