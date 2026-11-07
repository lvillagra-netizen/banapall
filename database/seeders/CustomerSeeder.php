<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@banapal.cl')->first();

        $customers = [
            ['tax_id_type' => 'RUT', 'tax_id' => '76.543.210-3', 'name' => 'Distribuidora Andina SpA', 'legal_name' => 'Distribuidora Andina SpA', 'email' => 'compras@andinadistribuye.cl', 'phone' => '+56 2 2456 7890', 'address' => 'Av. Los Carrera 1450', 'city' => 'Santiago', 'region' => 'Metropolitana', 'country_code' => 'CL', 'currency_code' => 'CLP'],
            ['tax_id_type' => 'RUT', 'tax_id' => '12.345.678-5', 'name' => 'Supermercados del Sur Ltda.', 'legal_name' => 'Supermercados del Sur Ltda.', 'email' => 'abastecimiento@superdelsur.cl', 'phone' => '+56 41 234 5678', 'address' => "O'Higgins 780", 'city' => 'Concepción', 'region' => 'Biobío', 'country_code' => 'CL', 'currency_code' => 'CLP'],
            ['tax_id_type' => 'RUT', 'tax_id' => '77.777.777-6', 'name' => 'Comercial Palta Chile Ltda.', 'legal_name' => 'Comercial Palta Chile Ltda.', 'email' => 'ventas@paltechile.cl', 'phone' => '+56 32 234 1122', 'address' => 'Av. España 2100', 'city' => 'Valparaíso', 'region' => 'Valparaíso', 'country_code' => 'CL', 'currency_code' => 'CLP'],
            ['tax_id_type' => 'RUC', 'tax_id' => '20123456789', 'name' => 'Mercado Central de Lima SAC', 'legal_name' => 'Mercado Central de Lima SAC', 'email' => 'compras@mercadocentral.pe', 'phone' => '+51 1 456 7890', 'address' => 'Av. Abancay 1200', 'city' => 'Lima', 'region' => 'Lima', 'country_code' => 'PE', 'currency_code' => 'PEN'],
            ['tax_id_type' => 'RUC', 'tax_id' => '20654321987', 'name' => 'Restaurantes Lima Norte SAC', 'legal_name' => 'Restaurantes Lima Norte SAC', 'email' => 'logistica@limanorte.pe', 'phone' => '+51 1 789 1234', 'address' => 'Av. Universitaria 3500', 'city' => 'Lima', 'region' => 'Lima', 'country_code' => 'PE', 'currency_code' => 'PEN'],
            ['tax_id_type' => 'Tax ID', 'tax_id' => 'US-8842-AVO', 'name' => 'Fresh Avocado Trading LLC', 'legal_name' => 'Fresh Avocado Trading LLC', 'email' => 'orders@freshavocadotrading.com', 'phone' => '+1 305 555 0142', 'address' => '1200 Brickell Ave, Suite 400', 'city' => 'Miami', 'region' => 'Florida', 'country_code' => 'US', 'currency_code' => 'USD'],
        ];

        foreach ($customers as $data) {
            Customer::query()->updateOrCreate(
                ['tax_id' => $data['tax_id']],
                array_merge($data, [
                    'credit_limit' => 2500000,
                    'is_active' => true,
                    'created_by' => $admin?->id,
                ]),
            );
        }
    }
}
