<?php

namespace App\Http\Controllers;

use App\Models\Chamber;
use App\Models\Warehouse;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChamberController extends Controller
{
    public function index(): View
    {
        return view('chambers.index', [
            'chambers' => Chamber::query()->with('warehouse')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = true;

        $chamber = Chamber::create($data);
        AuditLogger::record('chamber.created', $chamber, [], $chamber->only(['code', 'name', 'warehouse_id']), 'Cámara creada');

        return redirect()->route('chambers.index')->with('success', 'Cámara creada correctamente.');
    }

    public function update(Request $request, Chamber $chamber): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $old = $chamber->only(['warehouse_id', 'code', 'name', 'is_active']);
        $chamber->update($data);
        AuditLogger::record('chamber.updated', $chamber, $old, $chamber->only(['warehouse_id', 'code', 'name', 'is_active']), 'Cámara actualizada');

        return redirect()->route('chambers.index')->with('success', 'Cámara actualizada correctamente.');
    }

    public function destroy(Chamber $chamber): RedirectResponse
    {
        $chamber->delete();
        AuditLogger::record('chamber.deleted', $chamber, $chamber->only(['code', 'name']), [], 'Cámara eliminada');

        return redirect()->route('chambers.index')->with('success', 'Cámara eliminada correctamente.');
    }
}