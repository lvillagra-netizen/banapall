<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BANAPALL, packing y comercialización de paltas orgánicas certificadas. Operación integrada para Chile, Perú y exportación.">
    <title>BANAPALL · Paltas orgánicas con trazabilidad</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-slate-700">
    <header class="sticky top-0 z-40 border-b border-avocado-900/10 bg-cream/90 backdrop-blur">
        <div class="mx-auto flex h-20 max-w-7xl items-center gap-4 px-5 sm:px-8">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/banapal-mark.svg') }}" alt="BANAPALL" class="h-11 w-11 rounded-xl">
                <div>
                    <p class="font-display text-xl font-semibold tracking-wide text-ink">BANAPALL</p>
                    <p class="text-[9px] uppercase tracking-[0.24em] text-avocado-700">Packing orgánico</p>
                </div>
            </a>
            <nav class="ml-auto hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
                <a href="#productos" class="hover:text-avocado-800">Productos</a>
                <a href="#operacion" class="hover:text-avocado-800">Operación</a>
                <a href="#trazabilidad" class="hover:text-avocado-800">Trazabilidad</a>
                <a href="#contacto" class="hover:text-avocado-800">Contacto</a>
            </nav>
            <div class="ml-auto flex items-center gap-2 md:ml-0">
                @auth
                    <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-primary btn-sm sm:px-4 sm:py-2.5">Ir al sistema</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm sm:px-4 sm:py-2.5">Acceso interno</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden">
            <div class="absolute -right-32 top-10 h-[420px] w-[420px] rounded-full bg-avocado-200/60 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-24 h-[360px] w-[360px] rounded-full bg-avocado-100 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-avocado-200 bg-white px-3 py-1.5 text-xs font-semibold text-avocado-800 shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-avocado-500"></span>
                        Certificación orgánica · Chile, Perú y exportación
                    </p>
                    <h1 class="mt-6 max-w-2xl font-display text-5xl font-semibold leading-[1.06] tracking-tight text-ink sm:text-6xl">
                        Paltas orgánicas que llegan a destino <span class="text-avocado-700">con su historia completa.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                        Del palto a la mesa, BANAPALL controla packing, inventario y venta en una operación trazable. Este sitio es el prototipo funcional de la operación interna.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn btn-primary px-6 py-3">
                            Entrar al sistema
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m0 0-5-5m5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a href="#productos" class="btn btn-secondary px-6 py-3">Ver catálogo</a>
                    </div>

                    <dl class="mt-12 grid max-w-xl grid-cols-3 gap-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Base</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink">CLP</dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">USD hoy</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink">{{ $rates['USD'] ? '$'.number_format((float) $rates['USD'], 0, ',', '.') : '—' }}</dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">PEN hoy</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink">{{ $rates['PEN'] ? '$'.number_format((float) $rates['PEN'], 0, ',', '.') : '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="relative mx-auto w-full max-w-lg">
                    <div class="absolute inset-6 rotate-3 rounded-[2.5rem] bg-avocado-300/50"></div>
                    <div class="relative overflow-hidden rounded-[2.5rem] border border-avocado-200 bg-white p-4 shadow-2xl shadow-avocado-900/10">
                        <img src="{{ asset('images/products/hass.svg') }}" alt="Palta Hass orgánica BANAPALL" class="w-full rounded-[2rem]">
                        <div class="absolute bottom-7 left-7 right-7 rounded-2xl border border-white/60 bg-white/90 p-4 shadow-lg backdrop-blur">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-avocado-700">Lote destacado</p>
                                    <p class="mt-0.5 font-display text-lg font-semibold text-ink">Hass orgánica · Caja 10 kg</p>
                                </div>
                                <span class="badge badge-success">Trazable</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="productos" class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-avocado-700">Catálogo</p>
                    <h2 class="mt-2 font-display text-4xl font-semibold text-ink">Formatos para cada destino</h2>
                    <p class="mt-3 max-w-2xl text-slate-600">Precios referenciales en pesos chilenos. La operación interna calcula automáticamente USD y PEN con el tipo de cambio del día.</p>
                </div>
                @auth
                    <a href="{{ route('pos.index') }}" class="btn btn-secondary">Abrir POS</a>
                @endauth
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-avocado-900/10">
                        <div class="relative bg-avocado-50">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-52 w-full object-cover">
                            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold text-avocado-800 shadow-sm">{{ $product->category?->name ?? 'Palta orgánica' }}</span>
                        </div>
                        <div class="p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $product->sku }}</p>
                            <h3 class="mt-1 font-display text-xl font-semibold text-ink">{{ $product->name }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $product->variety }} · {{ $product->package_weight_kg }} kg · {{ ucfirst($product->unit) }}</p>
                            <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Precio CLP</p>
                                    <p class="text-xl font-semibold text-avocado-800">{{ \App\Support\Money::format($product->priceFor('CLP'), 'CLP') }}</p>
                                </div>
                                <div class="text-right text-[11px] text-slate-400">
                                    <p>USD {{ \App\Support\Money::format($product->priceFor('USD'), 'USD') }}</p>
                                    <p>PEN {{ \App\Support\Money::format($product->priceFor('PEN'), 'PEN') }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="operacion" class="border-y border-avocado-900/10 bg-avocado-950 py-16 text-white">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-avocado-300">Una sola operación</p>
                        <h2 class="mt-2 font-display text-4xl font-semibold">Menos planillas, más control.</h2>
                        <p class="mt-4 max-w-lg text-avocado-100/75">El prototipo conecta el Módulo de Ventas con inventario, folios, nota de venta, SII simulado y contabilidad.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['01', 'POS multi-moneda', 'Ventas en CLP, USD y PEN con tipo de cambio y pagos mixtos.'],
                            ['02', 'Inventario con trazabilidad', 'Cada salida genera un movimiento y descuenta stock real por bodega.'],
                            ['03', 'Nota de venta con folio', 'Correlativo controlado, PDF y anulación con reversa.'],
                            ['04', 'SII y contabilidad', 'Conectores simulados listos para reemplazar por integraciones reales.'],
                        ] as [$number, $title, $description])
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs font-semibold text-avocado-300">{{ $number }}</p>
                                <h3 class="mt-2 text-lg font-semibold text-white">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-avocado-100/70">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="trazabilidad" class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Nota de venta</p>
                            <p class="font-display text-2xl font-semibold text-ink">NV-000001</p>
                        </div>
                        <span class="badge badge-success">Emitida</span>
                    </div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">Cliente</span><span class="font-medium text-slate-700">Distribuidora Andina SpA</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Producto</span><span class="font-medium text-slate-700">Hass orgánica · Caja 10 kg</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Cantidad</span><span class="font-medium text-slate-700">4 cajas</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Stock posterior</span><span class="font-medium text-slate-700">176 cajas</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">SII</span><span class="badge badge-info">Simulado</span></div>
                        <div class="flex justify-between border-t border-slate-100 pt-3 text-base"><span class="font-semibold text-ink">Total</span><span class="font-semibold text-avocado-800">$ 137.564</span></div>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-avocado-700">Trazabilidad</p>
                    <h2 class="mt-2 font-display text-4xl font-semibold text-ink">Cada venta deja huella.</h2>
                    <p class="mt-4 text-slate-600">El sistema registra quién vendió, desde qué bodega salió el producto, qué folio se emitió y cómo se movió la contabilidad. La anulación revierte stock y asientos sin borrar la historia.</p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        @foreach (['Folios correlativos por tipo de documento', 'Movimientos de stock antes y después', 'Pagos en distintas monedas y medios', 'Auditoría de acciones críticas'] as $item)
                            <li class="flex items-start gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-avocado-100 text-avocado-700">✓</span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <section id="contacto" class="mx-auto max-w-7xl px-5 pb-20 sm:px-8">
            <div class="overflow-hidden rounded-3xl bg-avocado-800 px-6 py-10 text-white sm:px-10 lg:flex lg:items-center lg:justify-between lg:gap-10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-avocado-300">Contacto comercial</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold">Hablemos de tu próxima temporada.</h2>
                    <p class="mt-3 text-avocado-100/80">{{ $company['email'] }} · {{ $company['phone'] }}</p>
                </div>
                <a href="mailto:{{ $company['email'] }}" class="btn mt-6 bg-white text-avocado-900 hover:bg-avocado-50 lg:mt-0">Escribir a ventas</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-6 text-xs text-slate-400 sm:px-8">
            <p>© {{ now()->year }} BANAPALL SpA · {{ $company['activity'] }}</p>
            <p>Prototipo funcional · SII y pagos simulados</p>
        </div>
    </footer>
</body>
</html>
