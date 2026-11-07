<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';
    case AjustePositivo = 'ajuste_positivo';
    case AjusteNegativo = 'ajuste_negativo';
    case Anulacion = 'anulacion';
    case TrasladoSalida = 'traslado_salida';
    case TrasladoEntrada = 'traslado_entrada';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida por venta',
            self::AjustePositivo => 'Ajuste positivo',
            self::AjusteNegativo => 'Ajuste negativo',
            self::Anulacion => 'Reposición por anulación',
            self::TrasladoSalida => 'Traslado salida',
            self::TrasladoEntrada => 'Traslado entrada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Entrada => 'badge-success',
            self::Salida => 'badge-danger',
            self::AjustePositivo => 'badge-info',
            self::AjusteNegativo => 'badge-warning',
            self::Anulacion => 'badge-neutral',
            self::TrasladoSalida => 'badge-warning',
            self::TrasladoEntrada => 'badge-success',
        };
    }
}
