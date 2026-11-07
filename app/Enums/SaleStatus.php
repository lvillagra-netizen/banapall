<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Borrador = 'borrador';
    case Validada = 'validada';
    case Completada = 'completada';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Validada => 'Validada',
            self::Completada => 'Completada',
            self::Anulada => 'Anulada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Borrador => 'badge-neutral',
            self::Validada => 'badge-warning',
            self::Completada => 'badge-success',
            self::Anulada => 'badge-danger',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completada, self::Anulada], true);
    }
}
