<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => ProductCategory::query()->withCount('products')->orderBy('name')->get(),
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

        $category = ProductCategory::create($data);
        AuditLogger::record('category.created', $category, [], $category->only(['name', 'slug']), 'Categoría creada');

        return redirect()->route('categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $category->only(['name', 'description']);
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $category->update($data);
        AuditLogger::record('category.updated', $category, $old, $category->only(['name', 'description', 'is_active']), 'Categoría actualizada');

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'No se puede eliminar una categoría que tiene productos asociados.');
        }

        $category->delete();
        AuditLogger::record('category.deleted', $category, $category->only(['name', 'slug']), [], 'Categoría eliminada');

        return redirect()->route('categories.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
