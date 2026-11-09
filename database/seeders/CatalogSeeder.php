<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $paltas = ProductCategory::query()->firstOrCreate(
            ['slug' => 'paltas-organicas'],
            ['name' => 'Paltas orgánicas', 'description' => 'Paltas certificadas orgánicas para retail, food service y exportación.', 'is_active' => true],
        );

        $formatos = ProductCategory::query()->firstOrCreate(
            ['slug' => 'formatos-institucionales'],
            ['name' => 'Formatos institucionales', 'description' => 'Cajas y formatos de mayor volumen para distribuidores.', 'is_active' => true],
        );

        $products = [
            ['SKU' => 'HASS-10', 'name' => 'Palta Hass Orgánica Caja 10 kg', 'category' => $paltas, 'variety' => 'Hass', 'unit' => 'caja', 'weight' => 10, 'cost' => 17800, 'min' => 20, 'prices' => ['CLP' => 28900, 'USD' => 32.50, 'PEN' => 118.00], 'stock' => 180],
            ['SKU' => 'HASS-5', 'name' => 'Palta Hass Orgánica Caja 5 kg', 'category' => $paltas, 'variety' => 'Hass', 'unit' => 'caja', 'weight' => 5, 'cost' => 9600, 'min' => 25, 'prices' => ['CLP' => 15900, 'USD' => 17.90, 'PEN' => 65.00], 'stock' => 220],
            ['SKU' => 'FUERTE-10', 'name' => 'Palta Fuerte Orgánica Caja 10 kg', 'category' => $paltas, 'variety' => 'Fuerte', 'unit' => 'caja', 'weight' => 10, 'cost' => 16500, 'min' => 15, 'prices' => ['CLP' => 26900, 'USD' => 30.00, 'PEN' => 109.00], 'stock' => 120],
            ['SKU' => 'HASS-2', 'name' => 'Palta Hass Orgánica Bolsa 2 kg', 'category' => $paltas, 'variety' => 'Hass', 'unit' => 'bolsa', 'weight' => 2, 'cost' => 4600, 'min' => 40, 'prices' => ['CLP' => 7900, 'USD' => 8.90, 'PEN' => 32.00], 'stock' => 300],
            ['SKU' => 'HASS-20', 'name' => 'Palta Hass Orgánica Caja 20 kg', 'category' => $formatos, 'variety' => 'Hass', 'unit' => 'caja', 'weight' => 20, 'cost' => 33800, 'min' => 10, 'prices' => ['CLP' => 54900, 'USD' => 61.00, 'PEN' => 224.00], 'stock' => 80],
            ['SKU' => 'HASS-4', 'name' => 'Palta Hass Orgánica Caja 4 kg', 'category' => $paltas, 'variety' => 'Hass', 'unit' => 'caja', 'weight' => 4, 'cost' => 8100, 'min' => 30, 'prices' => ['CLP' => 13500, 'USD' => 15.00, 'PEN' => 55.00], 'stock' => 160],
            ['SKU' => 'FUERTE-5', 'name' => 'Palta Fuerte Orgánica Caja 5 kg', 'category' => $paltas, 'variety' => 'Fuerte', 'unit' => 'caja', 'weight' => 5, 'cost' => 9000, 'min' => 20, 'prices' => ['CLP' => 14900, 'USD' => 16.50, 'PEN' => 60.00], 'stock' => 140],
            ['SKU' => 'HASS-PALLET', 'name' => 'Pallet Palta Hass Orgánica 240 kg', 'category' => $formatos, 'variety' => 'Hass', 'unit' => 'pallet', 'weight' => 240, 'cost' => 385000, 'min' => 4, 'prices' => ['CLP' => 615000, 'USD' => 690.00, 'PEN' => 2520.00], 'stock' => 24],
        ];

        $central = Warehouse::query()->where('code', 'CENTRAL')->firstOrFail();
        $frio = Warehouse::query()->where('code', 'FRIO')->firstOrFail();

        foreach ($products as $data) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $data['SKU']],
                [
                    'product_category_id' => $data['category']->id,
                    'name' => $data['name'],
                    'slug' => strtolower($data['SKU']),
                    'variety' => $data['variety'],
                    'description' => 'Palta orgánica certificada, seleccionada y empacada por BANAPALL.',
                    'unit' => $data['unit'],
                    'package_weight_kg' => $data['weight'],
                    'is_organic' => true,
                    'certifier' => 'Ecocert',
                    'certification_code' => 'ECO-CL-'.str_replace('-', '', $data['SKU']),
                    'min_stock' => $data['min'],
                    'cost_clp' => $data['cost'],
                    'image_path' => 'images/products/'.($data['variety'] === 'Fuerte' ? 'fuerte.svg' : 'hass.svg'),
                    'is_active' => true,
                ],
            );

            foreach ($data['prices'] as $currency => $price) {
                ProductPrice::query()->updateOrCreate(
                    ['product_id' => $product->id, 'currency_code' => $currency],
                    ['price' => $price],
                );
            }

            Stock::query()->firstOrCreate(
                ['warehouse_id' => $central->id, 'chamber_id' => null, 'product_id' => $product->id],
                ['quantity' => $data['stock'], 'reserved' => 0],
            );

            Stock::query()->firstOrCreate(
                ['warehouse_id' => $frio->id, 'chamber_id' => null, 'product_id' => $product->id],
                ['quantity' => max(0, (int) round($data['stock'] * 0.15)), 'reserved' => 0],
            );
        }
    }
}
