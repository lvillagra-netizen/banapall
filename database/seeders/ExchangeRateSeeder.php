<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        for ($daysAgo = 14; $daysAgo >= 0; $daysAgo--) {
            $date = today()->subDays($daysAgo);
            $usd = 936 + (($daysAgo * 3) % 12);
            $pen = 246 + (($daysAgo * 2) % 8);

            ExchangeRate::query()->firstOrCreate(
                ['currency_code' => 'USD', 'effective_date' => $date->toDateString()],
                ['rate_to_clp' => $usd, 'source' => 'Carga inicial demo'],
            );

            ExchangeRate::query()->firstOrCreate(
                ['currency_code' => 'PEN', 'effective_date' => $date->toDateString()],
                ['rate_to_clp' => $pen, 'source' => 'Carga inicial demo'],
            );
        }
    }
}
