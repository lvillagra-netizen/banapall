<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::query()->where('code', 'CENTRAL')->first();

        $settings = [
            ['company.name', config('banapal.company.name'), 'empresa', 'string', 'Razón social de la empresa'],
            ['company.tax_id', config('banapal.company.tax_id'), 'empresa', 'string', 'RUT de la empresa'],
            ['company.address', config('banapal.company.address'), 'empresa', 'string', 'Dirección comercial'],
            ['company.activity', config('banapal.company.activity'), 'empresa', 'string', 'Giro comercial'],
            ['company.email', config('banapal.company.email'), 'empresa', 'string', 'Correo de contacto'],
            ['company.phone', config('banapal.company.phone'), 'empresa', 'string', 'Teléfono de contacto'],
            ['pos.default_warehouse', $warehouse?->id, 'pos', 'integer', 'Bodega por defecto del POS'],
            ['pos.default_currency', 'CLP', 'pos', 'string', 'Moneda por defecto del POS'],
            ['inventory.low_stock_threshold', '20', 'inventario', 'integer', 'Umbral de alerta de stock'],
            ['sales.allow_manual_discount', '1', 'ventas', 'boolean', 'Permitir descuentos manuales'],
            ['sales.max_cancel_amount_clp', '100000', 'ventas', 'decimal', 'Monto máximo CLP que un vendedor puede anular sin autorización'],
            ['notifications.sale_email', '1', 'notificaciones', 'boolean', 'Enviar comprobante por correo (simulado)'],
            ['sii.mode', 'simulado', 'sii', 'string', 'Modo de integración con SII'],
        ];

        foreach ($settings as [$key, $value, $group, $type, $description]) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : (string) $value, 'group' => $group, 'type' => $type, 'description' => $description],
            );
        }
    }
}
