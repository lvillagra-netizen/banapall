<?php

namespace App\Enums;

enum SiiStatus: string
{
    case Simulado = 'simulado';
    case Pendiente = 'pendiente';
    case Rechazado = 'rechazado';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Simulado => 'Simulado',
            self::Pendiente => 'Pendiente',
            self::Rechazado => 'Rechazado',
            self::Anulado => 'Anulado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Simulado => 'badge-info',
            self::Pendiente => 'badge-warning',
            self::Rechazado => 'badge-danger',
            self::Anulado => 'badge-neutral',
        };
    }
}
