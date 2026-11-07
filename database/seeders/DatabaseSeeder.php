<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            WarehouseSeeder::class,
            CatalogSeeder::class,
            ChamberBinSeeder::class,
            CustomerSeeder::class,
            ExchangeRateSeeder::class,
            FolioRangeSeeder::class,
            SettingSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
