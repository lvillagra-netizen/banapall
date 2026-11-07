<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Ventas = 'ventas';
    case Inventario = 'inventario';
    case Contabilidad = 'contabilidad';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Ventas => 'Ventas',
            self::Inventario => 'Inventario',
            self::Contabilidad => 'Contabilidad',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Admin => self::allPermissions(),
            self::Ventas => [
                'pos.access',
                'sales.view',
                'sales.create',
                'sales.validate',
                'sales.cancel',
                'sales.payment',
                'customers.view',
                'customers.manage',
                'products.view',
                'inventory.view',
                'billing.view',
                'reports.view',
            ],
            self::Inventario => [
                'sales.view',
                'products.view',
                'inventory.view',
                'inventory.manage',
                'customers.view',
                'reports.view',
            ],
            self::Contabilidad => [
                'sales.view',
                'products.view',
                'inventory.view',
                'customers.view',
                'billing.view',
                'billing.manage',
                'folios.manage',
                'accounting.view',
                'reports.view',
            ],
        };
    }

    public static function allPermissions(): array
    {
        return [
            'pos.access',
            'sales.view',
            'sales.create',
            'sales.validate',
            'sales.cancel',
            'sales.cancel_large',
            'sales.payment',
            'products.view',
            'products.manage',
            'inventory.view',
            'inventory.manage',
            'inventory.approve',
            'customers.view',
            'customers.manage',
            'billing.view',
            'billing.manage',
            'folios.manage',
            'accounting.view',
            'users.manage',
            'settings.manage',
            'audit.view',
            'reports.view',
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $role) => [$role->value => $role->label()])->all();
    }
}
