<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SaleService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Throwable;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Sale::withTrashed()->exists()) {
            return;
        }

        $seller = User::query()->where('email', 'ventas@banapal.cl')->first();
        $warehouse = Warehouse::query()->where('code', 'CENTRAL')->first();

        if (! $seller || ! $warehouse) {
            return;
        }

        $customers = Customer::query()->active()->orderBy('id')->get()->values();
        $products = Product::query()->active()->orderBy('id')->get()->values();
        $service = app(SaleService::class);

        $scenarios = [
            ['customer' => 0, 'items' => [[0, 4], [1, 2]], 'days' => 8, 'paid' => 1.0, 'method' => 'transferencia'],
            ['customer' => 3, 'items' => [[2, 3], [3, 4]], 'days' => 7, 'paid' => 1.0, 'method' => 'yape'],
            ['customer' => 1, 'items' => [[1, 5], [5, 2]], 'days' => 6, 'paid' => 1.0, 'method' => 'efectivo'],
            ['customer' => 5, 'items' => [[4, 2], [0, 2]], 'days' => 5, 'paid' => 1.0, 'method' => 'transferencia'],
            ['customer' => 2, 'items' => [[0, 3], [6, 2], [2, 1]], 'days' => 4, 'paid' => 1.0, 'method' => 'tarjeta'],
            ['customer' => 4, 'items' => [[3, 8], [5, 3]], 'days' => 3, 'paid' => 1.0, 'method' => 'plin'],
            ['customer' => 1, 'items' => [[1, 6], [3, 5]], 'days' => 2, 'paid' => 0.45, 'method' => 'transferencia'],
            ['customer' => 0, 'items' => [[6, 4], [5, 3]], 'days' => 1, 'paid' => 0.0, 'method' => 'transferencia'],
        ];

        foreach ($scenarios as $index => $scenario) {
            try {
                $customer = $customers->get($scenario['customer']);
                $items = collect($scenario['items'])->map(function (array $line) use ($products) {
                    $product = $products->get($line[0]);

                    return ['product_id' => $product->id, 'quantity' => $line[1]];
                })->all();

                $sale = $service->createDraft([
                    'customer_id' => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'currency_code' => $customer->currency_code,
                    'items' => $items,
                    'notes' => 'Venta de demostración generada por el prototipo.',
                ], $seller);

                $payments = [];

                if ($scenario['paid'] > 0) {
                    $amount = $scenario['paid'] >= 1
                        ? $sale->total
                        : Money::multiplyCurrency($sale->currency_code, $sale->total, (string) $scenario['paid']);

                    $payments[] = [
                        'method' => $scenario['method'],
                        'currency_code' => $sale->currency_code,
                        'amount' => $amount,
                        'reference' => 'DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    ];
                }

                $sale = $service->finalize($sale, $seller, $payments);
                $date = now()->subDays($scenario['days'])->setTime(10 + ($index % 6), 15);

                Sale::query()->whereKey($sale->id)->update([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'validated_at' => $date,
                ]);

                $sale->documents()->update([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'issued_at' => $date,
                ]);

                $sale->payments()->update([
                    'created_at' => $date,
                    'updated_at' => $date,
                    'paid_at' => $date,
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $customer = $customers->first();

            $service->createDraft([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'currency_code' => $customer->currency_code,
                'items' => [
                    ['product_id' => $products->get(1)->id, 'quantity' => 2],
                    ['product_id' => $products->get(3)->id, 'quantity' => 3],
                ],
                'notes' => 'Borrador de demostración pendiente de validación.',
            ], $seller);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
