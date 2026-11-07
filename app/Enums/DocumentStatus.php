<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Emitido = 'emitido';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Emitido => 'Emitido',
            self::Anulado => 'Anulado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Emitido => 'badge-success',
            self::Anulado => 'badge-danger',
        };
    }
}
