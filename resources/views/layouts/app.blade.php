<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-stone-50">
<div x-data="{ sidebarOpen: false }" class="min-h-screen">
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-avocado-950 text-white transition-transform duration-200 lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen }">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
            <img src="{{ asset('images/banapal-mark.svg') }}" alt="BANAPALL" class="h-10 w-10 rounded-xl">
            <div>
                <p class="font-display text-xl font-semibold tracking-wide">BANAPALL</p>
                <p class="text-[10px] uppercase tracking-[0.24em] text-avocado-300">Packing orgánico</p>
            </div>
            <button type="button" class="ml-auto rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Cerrar menú">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            @role('admin')
                <p class="sidebar-label">Dirección</p>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'sidebar-link-active' : '' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z" stroke-linejoin="round"/></svg>
                    Panel general
                </a>
            @endrole

            @canany(['pos.access', 'sales.view'])
                <p class="sidebar-label">Ventas</p>
                @can('pos.access')
                    <a href="{{ route('pos.index') }}" class="sidebar-link {{ request()->routeIs('pos.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h2l2.2 9.2a2 2 0 0 0 2 1.5h6.8a2 2 0 0 0 2-1.6L20 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                        Módulo de Ventas
                    </a>
                @endcan
                @can('sales.view')
                    <a href="{{ route('sales.index') }}" class="sidebar-link {{ request()->routeIs('sales.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2Z" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke-linecap="round"/></svg>
                        Ventas y notas
                    </a>
                @endcan
            @endcanany

            @canany(['customers.view', 'products.view', 'inventory.view'])
                <p class="sidebar-label">Catálogo e inventario</p>
                @can('customers.view')
                    <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->routeIs('customers.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20" stroke-linecap="round"/><circle cx="9.5" cy="7.5" r="3.5"/><path d="M17 11h4M19 9v4" stroke-linecap="round"/></svg>
                        Clientes
                    </a>
                @endcan
                @can('products.view')
                    <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->routeIs('products.*', 'categories.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" stroke-linejoin="round"/><path d="m4.4 7.7 7.6 4.2 7.6-4.2M12 12v9" stroke-linejoin="round"/></svg>
                        Productos
                    </a>
                @endcan
                @can('inventory.view')
                    <a href="{{ route('inventory.index') }}" class="sidebar-link {{ request()->routeIs('inventory.index') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 8.5 12 4l9 4.5v7L12 20l-9-4.5v-7Z" stroke-linejoin="round"/><path d="M3 8.5 12 13l9-4.5M12 13v7" stroke-linejoin="round"/></svg>
                        Bodega Local
                    </a>
                    <a href="{{ route('inventory.movements') }}" class="sidebar-link {{ request()->routeIs('inventory.movements') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h12m0 0-3-3m3 3-3 3M20 17H8m0 0 3 3m-3-3 3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Movimientos
                    </a>
                @endcan
                @can('inventory.manage')
                    <a href="{{ route('warehouses.index') }}" class="sidebar-link {{ request()->routeIs('warehouses.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 20V9l8-5 8 5v11" stroke-linejoin="round"/><path d="M9 20v-6h6v6" stroke-linejoin="round"/></svg>
                        Bodegas
                    </a>
                    <a href="{{ route('chambers.index') }}" class="sidebar-link {{ request()->routeIs('chambers.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="4" width="7" height="16" rx="1"/><rect x="13" y="4" width="7" height="16" rx="1"/></svg>
                        Cámaras
                    </a>
                    <a href="{{ route('bins.index') }}" class="sidebar-link {{ request()->routeIs('bins.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg>
                        BINS
                    </a>
                @endcan
                @can('products.manage')
                    <a href="{{ route('calibers.index') }}" class="sidebar-link {{ request()->routeIs('calibers.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/></svg>
                        Calibres
                    </a>
                @endcan
            @endcanany

            @canany(['billing.view', 'folios.manage', 'reports.view'])
                <p class="sidebar-label">Administración</p>
                @can('billing.view')
                    <a href="{{ route('billing.documents') }}" class="sidebar-link {{ request()->routeIs('billing.documents') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2Z" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h3" stroke-linecap="round"/></svg>
                        Documentos
                    </a>
                @endcan
                @can('folios.manage')
                    <a href="{{ route('billing.folios') }}" class="sidebar-link {{ request()->routeIs('billing.folios') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h14v16H5V4Z" stroke-linejoin="round"/><path d="M8 8h8M8 12h8M8 16h4" stroke-linecap="round"/></svg>
                        Folios
                    </a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 20V10m5 10V4m5 16v-7m5 7V8" stroke-linecap="round"/></svg>
                        Reportes
                    </a>
                @endcan
            @endcanany

            @canany(['users.manage', 'settings.manage', 'audit.view'])
                <p class="sidebar-label">Sistema</p>
                @can('users.manage')
                    <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="8" r="3.2"/><path d="M3 20v-1.5a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4V20M17 8h4M19 6v4" stroke-linecap="round"/></svg>
                        Usuarios
                    </a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21h-4v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3v-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V3h4v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.1v4h-.1a1.7 1.7 0 0 0-1.5 1Z" stroke-linejoin="round"/></svg>
                        Configuración
                    </a>
                @endcan
                @can('audit.view')
                    <a href="{{ route('audit.index') }}" class="sidebar-link {{ request()->routeIs('audit.*') ? 'sidebar-link-active' : '' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 8v5l3 2" stroke-linecap="round"/><circle cx="12" cy="12" r="8"/></svg>
                        Auditoría
                    </a>
                @endcan
            @endcanany
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-avocado-500 text-sm font-bold text-white">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-avocado-300">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white" title="Cerrar sesión">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M14 8l4 4-4 4M18 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/85 backdrop-blur">
            <div class="flex h-20 items-center gap-4 px-4 sm:px-6 lg:px-8">
                <button type="button" class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm lg:hidden" @click="sidebarOpen = true" aria-label="Abrir menú">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">@yield('eyebrow', 'BANAPALL · Prototipo funcional')</p>
                    <h1 class="truncate font-display text-2xl font-semibold text-ink">@yield('title', 'Panel')</h1>
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 sm:inline-flex">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Datos simulados
                    </span>
                    <div class="hidden text-right sm:block">
                        <p class="text-xs font-semibold text-slate-600">{{ now()->locale('es')->isoFormat('ddd D MMM') }}</p>
                        <p class="text-[11px] text-slate-400">{{ now()->format('H:i') }}</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="mx-auto flex w-full max-w-[1500px] flex-wrap items-center justify-between gap-2 px-4 pb-8 text-xs text-slate-400 sm:px-6 lg:px-8">
            <p>BANAPALL SpA · Prototipo</p>
            <p>{{ config('app.name') }} · {{ now()->format('Y') }}</p>
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
