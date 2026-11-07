<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('warehouses.index', [
            'warehouses' => Warehouse::query()->withCount('chambers')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = true;

        $warehouse = Warehouse::create($data);
        AuditLogger::record('warehouse.created', $warehouse, [], $warehouse->only(['code', 'name']), 'Bodega creada');

        return redirect()->route('warehouses.index')->with('success', 'Bodega creada correctamente.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code,'.$warehouse->id],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $old = $warehouse->only(['code', 'name', 'address', 'is_active']);
        $warehouse->update($data);
        AuditLogger::record('warehouse.updated', $warehouse, $old, $warehouse->only(['code', 'name', 'address', 'is_active']), 'Bodega actualizada');

        return redirect()->route('warehouses.index')->with('success', 'Bodega actualizada correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->stocks()->exists() || $warehouse->sales()->exists()) {
            return redirect()->route('warehouses.index')->with('error', 'No se puede eliminar una bodega con stock o ventas asociadas.');
        }

        $warehouse->delete();
        AuditLogger::record('warehouse.deleted', $warehouse, $warehouse->only(['code', 'name']), [], 'Bodega eliminada');

        return redirect()->route('warehouses.index')->with('success', 'Bodega eliminada correctamente.');
    }
}