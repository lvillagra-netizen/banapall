<?php

namespace App\Http\Controllers;

use App\Exceptions\SaleValidationException;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->with(['category', 'prices', 'stocks'])->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('variety', 'ilike', "%{$search}%");
            });
        }

        if ($category = $request->integer('category')) {
            $query->where('product_category_id', $category);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return view('products.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => ProductCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['is_active' => true, 'is_organic' => true, 'unit' => 'caja']),
            'categories' => ProductCategory::query()->orderBy('name')->get(),
            'currencies' => config('banapal.currencies'),
            'prices' => collect(config('banapal.currencies'))->map(fn () => null)->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $product = Product::create($data);
        $this->savePrices($product, $request);
        AuditLogger::record('product.created', $product, [], $product->only(['sku', 'name', 'cost_clp']), 'Producto creado');

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Producto creado correctamente. Registra stock inicial desde Inventario.');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.edit', $product);
    }

    public function edit(Product $product): View
    {
        $product->load(['prices', 'stocks.warehouse', 'category']);

        return view('products.form', [
            'product' => $product,
            'categories' => ProductCategory::query()->orderBy('name')->get(),
            'currencies' => config('banapal.currencies'),
            'prices' => $product->prices->pluck('price', 'currency_code')->all(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $old = $product->only(['sku', 'name', 'cost_clp', 'is_active']);
        $product->update($data);
        $this->savePrices($product, $request);
        AuditLogger::record('product.updated', $product, $old, $product->only(['sku', 'name', 'cost_clp', 'is_active']), 'Producto actualizado');

        return redirect()
            ->route('products.edit', $product)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists()) {
            $product->update(['is_active' => false]);
            AuditLogger::record('product.deactivated', $product, [], ['is_active' => false], 'Producto desactivado por tener ventas');

            return redirect()
                ->route('products.index')
                ->with('success', 'El producto tiene ventas asociadas, por lo que fue desactivado en lugar de eliminarse.');
        }

        $product->delete();
        AuditLogger::record('product.deleted', $product, $product->only(['sku', 'name']), [], 'Producto eliminado');

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku'.($product ? ",{$product->id}" : '')],
            'name' => ['required', 'string', 'max:255'],
            'variety' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:3000'],
            'unit' => ['required', 'string', 'max:30'],
            'package_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'certifier' => ['nullable', 'string', 'max:80'],
            'certification_code' => ['nullable', 'string', 'max:80'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'cost_clp' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'prices' => ['required', 'array'],
            'prices.CLP' => ['required', 'numeric', 'min:0'],
            'prices.USD' => ['nullable', 'numeric', 'min:0'],
            'prices.PEN' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['sku'] = Str::upper(trim($data['sku']));
        $data['slug'] = $product?->slug ?: Str::slug($data['name'].'-'.$data['sku']);
        $data['is_organic'] = $request->boolean('is_organic');
        $data['is_active'] = $request->boolean('is_active');
        $data['package_weight_kg'] = $data['package_weight_kg'] ?? 0;
        $data['min_stock'] = $data['min_stock'] ?? 0;

        if ($request->hasFile('image')) {
            $oldPath = $product?->image_path;

            if ($oldPath && ! Str::startsWith($oldPath, ['images/', 'http://', 'https://'])) {
                Storage::disk('public')->delete($oldPath);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        unset($data['image']);

        return $data;
    }

    private function savePrices(Product $product, Request $request): void
    {
        foreach (array_keys(config('banapal.currencies')) as $currency) {
            $price = $request->input("prices.{$currency}");

            if ($price === null || $price === '') {
                $product->prices()->where('currency_code', $currency)->delete();
                continue;
            }

            ProductPrice::query()->updateOrCreate(
                ['product_id' => $product->id, 'currency_code' => $currency],
                ['price' => $price],
            );
        }
    }
}
