<?php

namespace App\Enums;

enum DocumentType: string
{
    case NotaVenta = 'nota_venta';
    case Boleta = 'boleta';
    case Factura = 'factura';

    public function label(): string
    {
        return (string) config("banapal.document_types.{$this->value}.label", ucfirst($this->value));
    }

    public function prefix(): string
    {
        return (string) config("banapal.document_types.{$this->value}.prefix", 'NV');
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
