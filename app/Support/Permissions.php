<?php

namespace App\Support;

use App\Enums\Role;

class Permissions
{
    public static function grouped(): array
    {
        return [
            'Operación de ventas' => [
                'pos.access' => 'Acceder al POS',
                'sales.view' => 'Ver ventas',
                'sales.create' => 'Crear ventas',
                'sales.validate' => 'Validar y emitir ventas',
                'sales.cancel' => 'Anular ventas',
                'sales.cancel_large' => 'Anular ventas de monto elevado',
                'sales.payment' => 'Registrar pagos',
            ],
            'Catálogo e inventario' => [
                'products.view' => 'Ver productos',
                'products.manage' => 'Administrar productos',
                'inventory.view' => 'Ver inventario',
                'inventory.manage' => 'Administrar inventario',
                'inventory.approve' => 'Autorizar movimientos de inventario',
            ],
            'Clientes' => [
                'customers.view' => 'Ver clientes',
                'customers.manage' => 'Administrar clientes',
            ],
            'Facturación y contabilidad' => [
                'billing.view' => 'Ver documentos',
                'billing.manage' => 'Emitir y administrar documentos',
                'folios.manage' => 'Administrar folios',
                'accounting.view' => 'Ver contabilidad',
            ],
            'Administración' => [
                'users.manage' => 'Administrar usuarios',
                'settings.manage' => 'Administrar configuración',
                'audit.view' => 'Ver auditoría',
                'reports.view' => 'Ver reportes',
            ],
        ];
    }

    public static function all(): array
    {
        return Role::allPermissions();
    }
}
