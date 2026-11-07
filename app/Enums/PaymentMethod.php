<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Tarjeta = 'tarjeta';
    case Credito = 'credito';
    case ChequeFecha = 'cheque_fecha';
    case Yape = 'yape';
    case Plin = 'plin';
    case Otro = 'otro';

    public function label(): string
    {
        return (string) config("banapal.payment_methods.{$this->value}.label", ucfirst($this->value));
    }

    public function allowedCurrencies(): array
    {
        return (array) config("banapal.payment_methods.{$this->value}.currencies", Currency::values());
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $method) => [$method->value => $method->label()])->all();
    }
}
