@extends('layouts.app')

@section('title', $user->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('eyebrow', 'Sistema · ' . ($user->exists ? 'Editar usuario' : 'Nuevo usuario'))

@section('content')
    <div class="mb-5">
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver a usuarios
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <div class="card">
            <div class="card-header"><h2 class="section-title">Datos del usuario</h2></div>
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card-body space-y-4">
                @csrf
                @if ($user->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Nombre *</label>
                        <input name="name" required value="{{ old('name', $user->name) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Email *</label>
                        <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="input">
                    </div>
                    <div>
                        <label class="label">Teléfono</label>
                        <input name="phone" value="{{ old('phone', $user->phone) }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Rol *</label>
                        <select name="role" class="input">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $user->roles->first()?->name) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">El rol define las secciones visibles y las acciones permitidas.</p>
                    </div>
                    <div>
                        <label class="label">Contraseña {{ $user->exists ? '' : '*' }}</label>
                        <input type="password" name="password" @required(! $user->exists) class="input" autocomplete="new-password">
                        @if ($user->exists)
                            <p class="mt-1 text-[11px] text-slate-400">Dejar en blanco para conservar la actual.</p>
                        @endif
                    </div>
                    <div>
                        <label class="label">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-avocado-700 focus:ring-avocado-500">
                    Usuario activo
                </label>

                <div class="flex gap-2 pt-2">
                    <button class="btn btn-primary">{{ $user->exists ? 'Guardar cambios' : 'Crear usuario' }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Permisos por rol</h2></div>
                <div class="card-body space-y-3 text-xs text-slate-500">
                    <div><p class="font-semibold text-slate-700">Ventas</p><p class="mt-0.5">POS, clientes, validación, pagos, documentos y reportes.</p></div>
                    <div><p class="font-semibold text-slate-700">Inventario</p><p class="mt-0.5">Stock, ingresos, traslados, ajustes y consulta de ventas.</p></div>
                    <div><p class="font-semibold text-slate-700">Contabilidad</p><p class="mt-0.5">Documentos, folios, libro contable y reportes.</p></div>
                    <div><p class="font-semibold text-slate-700">Administrador</p><p class="mt-0.5">Acceso completo, usuarios, configuración y auditoría.</p></div>
                </div>
            </div>

            @if ($user->exists && ! auth()->user()->is($user))
                <div class="card border-rose-200">
                    <div class="card-body">
                        <p class="text-sm font-semibold text-rose-700">Desactivar usuario</p>
                        <p class="mt-1 text-xs text-slate-500">No podrá iniciar sesión. Su historial se conserva.</p>
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-3" onsubmit="return confirm('¿Desactivar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Desactivar usuario</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
