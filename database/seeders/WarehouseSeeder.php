<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::query()->firstOrCreate(
            ['code' => 'CENTRAL'],
            ['name' => 'Bodega Central', 'address' => 'Bodega central de packing', 'is_active' => true],
        );

        Warehouse::query()->firstOrCreate(
            ['code' => 'FRIO'],
            ['name' => 'Bodega Fría', 'address' => 'Bodega fría de packing', 'is_active' => true],
        );
    }
}
