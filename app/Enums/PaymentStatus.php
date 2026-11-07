<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagado = 'pagado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Parcial',
            self::Pagado => 'Pagado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pendiente => 'badge-neutral',
            self::Parcial => 'badge-warning',
            self::Pagado => 'badge-success',
        };
    }
}
