@extends('layouts.app')

@section('title', 'Usuarios')
@section('eyebrow', 'Sistema · Usuarios y roles')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Cada usuario opera según su rol. Un usuario inactivo no puede iniciar sesión.</p>
        <a href="{{ route('users.create') }}" class="btn btn-primary">Nuevo usuario</a>
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="card mb-5">
        <div class="card-body flex flex-col gap-3 sm:flex-row">
            <input name="search" value="{{ request('search') }}" class="input flex-1" placeholder="Buscar por nombre o email…">
            <button class="btn btn-primary btn-sm">Buscar</button>
            <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Equipo</h2>
            <span class="badge badge-neutral">{{ $users->total() }} usuarios</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Último acceso</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-avocado-100 text-xs font-bold text-avocado-800">{{ mb_substr($user->name, 0, 1) }}</div>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $user->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $user->phone ?: 'Sin teléfono' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-xs">{{ $user->email }}</td>
                            <td><span class="badge badge-info">{{ $user->roleLabel() }}</span></td>
                            <td class="text-xs text-slate-500">{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                            <td><span class="badge {{ $user->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-sm">Editar</a>
                                    @if (! auth()->user()->is($user))
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('¿Desactivar este usuario?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-sm text-rose-500">Desactivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-slate-400">No hay usuarios que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
