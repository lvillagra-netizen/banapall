<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Acceso') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-avocado-950 p-12 text-white lg:flex lg:flex-col">
            <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-avocado-700/40 blur-2xl"></div>
            <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-avocado-500/20 blur-3xl"></div>
            <a href="{{ route('landing') }}" class="relative flex items-center gap-3">
                <img src="{{ asset('images/banapal-mark.svg') }}" alt="BANAPALL" class="h-12 w-12 rounded-2xl">
                <div>
                    <p class="font-display text-2xl font-semibold tracking-wide">BANAPALL</p>
                    <p class="text-[10px] uppercase tracking-[0.28em] text-avocado-300">Packing orgánico</p>
                </div>
            </a>

            <div class="relative my-auto max-w-lg">
                <p class="mb-5 inline-flex rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-avocado-200">Prototipo funcional · 2026</p>
                <h2 class="font-display text-5xl font-semibold leading-tight">Del palto a la mesa, con trazabilidad de verdad.</h2>
                <p class="mt-6 text-lg leading-relaxed text-avocado-100/80">Módulo de Ventas, inventario, notas de venta y contabilidad simulada en una sola operación.</p>

                <div class="mt-10 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-semibold">CLP</p>
                        <p class="mt-1 text-[11px] uppercase tracking-wide text-avocado-300">Moneda base</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-semibold">USD</p>
                        <p class="mt-1 text-[11px] uppercase tracking-wide text-avocado-300">Exportación</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-semibold">PEN</p>
                        <p class="mt-1 text-[11px] uppercase tracking-wide text-avocado-300">Perú</p>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-avocado-200/60">SII y contabilidad operan en modo simulado para este prototipo.</p>
        </div>

        <div class="flex items-center justify-center px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <a href="{{ route('landing') }}" class="mb-10 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/banapal-mark.svg') }}" alt="BANAPALL" class="h-11 w-11 rounded-xl">
                    <div>
                        <p class="font-display text-2xl font-semibold text-ink">BANAPALL</p>
                        <p class="text-[10px] uppercase tracking-[0.24em] text-avocado-700">Packing orgánico</p>
                    </div>
                </a>
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
