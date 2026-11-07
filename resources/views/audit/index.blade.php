@extends('layouts.app')

@section('title', 'Auditoría')
@section('eyebrow', 'Sistema · Registro de acciones')

@section('content')
    <div class="mb-5">
        <p class="max-w-xl text-sm text-slate-500">Trazabilidad de acciones críticas: ventas, pagos, inventario, clientes, usuarios y configuración.</p>
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="label">Buscar</label>
                <input name="search" value="{{ request('search') }}" class="input" placeholder="Acción, descripción o modelo…">
            </div>
            <div>
                <label class="label">Usuario</label>
                <select name="user" class="input">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) request('user') === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('audit.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Bitácora</h2>
            <span class="badge badge-neutral">{{ $logs->total() }} eventos</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Descripción</th>
                        <th>Objeto</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php($badge = match (true) {
                            str_contains($log->action, 'cancelled'), str_contains($log->action, 'deleted') => 'badge-danger',
                            str_contains($log->action, 'created'), str_contains($log->action, 'validated'), str_contains($log->action, 'payment') => 'badge-success',
                            str_contains($log->action, 'updated'), str_contains($log->action, 'adjusted') => 'badge-warning',
                            default => 'badge-neutral',
                        })
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="text-xs">
                                <p class="font-medium text-slate-700">{{ $log->user?->name ?? 'Sistema' }}</p>
                                <p class="text-slate-400">{{ $log->user?->email }}</p>
                            </td>
                            <td><span class="badge {{ $badge }}">{{ $log->action }}</span></td>
                            <td class="max-w-md text-xs text-slate-600">{{ $log->description ?: '—' }}</td>
                            <td class="text-xs text-slate-400">
                                @if ($log->auditable_type)
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-xs text-slate-400">{{ $log->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-sm text-slate-400">No hay eventos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
@endsection
