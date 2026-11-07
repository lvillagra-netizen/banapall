<?php

namespace App\Http\Controllers;

use App\Models\Bin;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BinController extends Controller
{
    public function index(): View
    {
        return view('bins.index', [
            'bins' => Bin::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:bins,code'],
            'name' => ['required', 'string', 'max:120'],
            'capacity_kg' => ['nullable', 'numeric', 'min:0'],
            'cost_clp' => ['required', 'numeric', 'min:0'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = true;
        $bin = Bin::create($data);
        AuditLogger::record('bin.created', $bin, [], $bin->only(['code', 'name', 'cost_clp']), 'Bin creado');

        return redirect()->route('bins.index')->with('success', 'Bin creado correctamente.');
    }

    public function update(Request $request, Bin $bin): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:bins,code,'.$bin->id],
            'name' => ['required', 'string', 'max:120'],
            'capacity_kg' => ['nullable', 'numeric', 'min:0'],
            'cost_clp' => ['required', 'numeric', 'min:0'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $old = $bin->only(['code', 'name', 'cost_clp', 'is_active']);
        $bin->update($data);
        AuditLogger::record('bin.updated', $bin, $old, $bin->only(['code', 'name', 'cost_clp', 'is_active']), 'Bin actualizado');

        return redirect()->route('bins.index')->with('success', 'Bin actualizado correctamente.');
    }

    public function destroy(Bin $bin): RedirectResponse
    {
        $bin->delete();
        AuditLogger::record('bin.deleted', $bin, $bin->only(['code', 'name']), [], 'Bin eliminado');

        return redirect()->route('bins.index')->with('success', 'Bin eliminado correctamente.');
    }
}