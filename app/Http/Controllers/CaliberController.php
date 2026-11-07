<?php

namespace App\Http\Controllers;

use App\Models\Caliber;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CaliberController extends Controller
{
    public function index(): View
    {
        return view('calibers.index', [
            'calibers' => Caliber::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = true;

        $caliber = Caliber::create($data);
        AuditLogger::record('caliber.created', $caliber, [], $caliber->only(['name', 'slug']), 'Calibre creado');

        return redirect()->route('calibers.index')->with('success', 'Calibre creado correctamente.');
    }

    public function update(Request $request, Caliber $caliber): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $caliber->only(['name', 'description']);
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $caliber->update($data);
        AuditLogger::record('caliber.updated', $caliber, $old, $caliber->only(['name', 'description', 'is_active']), 'Calibre actualizado');

        return redirect()->route('calibers.index')->with('success', 'Calibre actualizado correctamente.');
    }

    public function destroy(Caliber $caliber): RedirectResponse
    {
        if ($caliber->products()->exists()) {
            return redirect()->route('calibers.index')->with('error', 'No se puede eliminar un calibre con productos asociados.');
        }

        $caliber->delete();
        AuditLogger::record('caliber.deleted', $caliber, $caliber->only(['name', 'slug']), [], 'Calibre eliminado');

        return redirect()->route('calibers.index')->with('success', 'Calibre eliminado correctamente.');
    }
}