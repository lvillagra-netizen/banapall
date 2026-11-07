<?php

namespace Database\Seeders;

use App\Models\Bin;
use App\Models\Chamber;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ChamberBinSeeder extends Seeder
{
    public function run(): void
    {
        $central = Warehouse::query()->where('code', 'CENTRAL')->first();
        $frio = Warehouse::query()->where('code', 'FRIO')->first();

        if (! $central || ! $frio) {
            return;
        }

        $camaras = [
            ['warehouse_id' => $central->id, 'code' => 'CAM-01', 'name' => 'Cámara 1', 'description' => 'Cámara de enfriamiento central 1'],
            ['warehouse_id' => $central->id, 'code' => 'CAM-02', 'name' => 'Cámara 2', 'description' => 'Cámara de enfriamiento central 2'],
            ['warehouse_id' => $frio->id, 'code' => 'CAMF-01', 'name' => 'Cámara fría 1', 'description' => 'Cámara fría de la bodega FRIO'],
        ];

        foreach ($camaras as $data) {
            Chamber::query()->firstOrCreate(
                ['warehouse_id' => $data['warehouse_id'], 'code' => $data['code']],
                ['name' => $data['name'], 'description' => $data['description'], 'is_active' => true],
            );
        }

        $bins = [
            ['code' => 'BIN-A1', 'name' => 'Bin A1', 'capacity_kg' => 500],
            ['code' => 'BIN-A2', 'name' => 'Bin A2', 'capacity_kg' => 500],
            ['code' => 'BIN-B1', 'name' => 'Bin B1', 'capacity_kg' => 800],
            ['code' => 'BIN-B2', 'name' => 'Bin B2', 'capacity_kg' => 800],
        ];

        foreach ($bins as $data) {
            Bin::query()->firstOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'capacity_kg' => $data['capacity_kg'], 'cost_clp' => 0, 'is_active' => true],
            );
        }

        $camara1 = Chamber::query()->where('warehouse_id', $central->id)->where('code', 'CAM-01')->first();

        if (! $camara1) {
            return;
        }

        $products = Product::query()->active()->get();

        foreach ($products as $product) {
            $base = Stock::query()
                ->where('warehouse_id', $central->id)
                ->where('product_id', $product->id)
                ->whereNull('chamber_id')
                ->first();

            if (! $base || (float) $base->quantity < 50) {
                continue;
            }

            $chamberStock = Stock::query()
                ->where('warehouse_id', $central->id)
                ->where('product_id', $product->id)
                ->where('chamber_id', $camara1->id)
                ->first();

            if ($chamberStock) {
                continue;
            }

            $move = 30;

            $base->update(['quantity' => (string) ((float) $base->quantity - $move)]);

            Stock::query()->create([
                'warehouse_id' => $central->id,
                'chamber_id' => $camara1->id,
                'product_id' => $product->id,
                'quantity' => (string) $move,
                'reserved' => 0,
            ]);
        }
    }
}
