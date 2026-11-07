@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-avocado-700">Acceso interno</p>
        <h1 class="mt-2 font-display text-4xl font-semibold text-ink">Bienvenido de vuelta</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-500">Ingresa con tu cuenta para operar el POS, inventario y administración de BANAPAL.</p>
    </div>

    @include('partials.flash')

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="label">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', 'admin@banapal.cl') }}" autocomplete="email" required autofocus class="input" placeholder="nombre@banapal.cl">
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Contraseña</label>
                <span class="text-[11px] text-slate-400">Mínimo 8 caracteres</span>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="input" placeholder="••••••••">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-avocado-600 focus:ring-avocado-500">
            Mantener sesión iniciada
        </label>
        <button type="submit" class="btn btn-primary w-full py-3">
            Ingresar al sistema
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m0 0-5-5m5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cuentas de demostración</p>
        <div class="mt-3 grid gap-2 text-xs text-slate-600">
            <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span>Administrador</span><code class="text-avocado-800">admin@banapal.cl</code></div>
            <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span>Ventas</span><code class="text-avocado-800">ventas@banapal.cl</code></div>
            <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span>Inventario</span><code class="text-avocado-800">inventario@banapal.cl</code></div>
            <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span>Contabilidad</span><code class="text-avocado-800">contabilidad@banapal.cl</code></div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Contraseña única de demo: <strong class="text-slate-600">Banapal2026!</strong></p>
    </div>

    <p class="mt-6 text-center text-xs text-slate-400">
        <a href="{{ route('landing') }}" class="font-semibold text-avocado-700 hover:underline">Volver al sitio público</a>
    </p>
@endsection
