<?php

namespace App\Services;

use App\Contracts\AccountingConnector;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\SaleValidationException;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRates,
        private readonly AccountingConnector $accounting,
    ) {
    }

    public function record(Sale $sale, array $payments, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $payments, $user) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! in_array($sale->status, [SaleStatus::Validada, SaleStatus::Completada], true)) {
                throw new SaleValidationException(['errors' => ['Solo se pueden registrar pagos sobre ventas validadas.']]);
            }

            if ($payments === []) {
                throw new SaleValidationException(['errors' => ['Debe registrar al menos un pago.']]);
            }

            $saleCurrency = $sale->currency_code;
            $saleRate = (string) $sale->exchange_rate;
            $paidTotal = $this->confirmedTotal($sale);
            $available = Money::subtractCurrency($saleCurrency, $sale->total, $paidTotal);

            foreach ($payments as $paymentData) {
                $method = PaymentMethod::tryFrom((string) ($paymentData['method'] ?? ''));

                if (! $method) {
                    throw new SaleValidationException(['errors' => ['El medio de pago seleccionado no es válido.']]);
                }

                $currency = strtoupper((string) ($paymentData['currency_code'] ?? $saleCurrency));
                $amount = Money::round($paymentData['amount'] ?? 0, $currency);

                if (Money::compare($amount, '0') <= 0) {
                    throw new SaleValidationException(['errors' => ['El monto de cada pago debe ser mayor que cero.']]);
                }

                if (! in_array($currency, $method->allowedCurrencies(), true)) {
                    throw new SaleValidationException(['errors' => ["{$method->label()} no admite pagos en {$currency}."]]);
                }

                $paymentRate = $this->exchangeRates->rateFor($currency);

                if ($currency === $saleCurrency) {
                    $amountInSaleCurrency = $amount;
                } else {
                    $amountClp = Money::multiply($amount, $paymentRate);
                    $amountInSaleCurrency = $saleCurrency === 'CLP'
                        ? Money::round($amountClp, 'CLP')
                        : Money::round(Money::divide($amountClp, $saleRate), $saleCurrency);
                }

                if (Money::compare($amountInSaleCurrency, $available) > 0) {
                    throw new SaleValidationException([
                        'errors' => ["El pago excede el saldo pendiente de ".Money::format($available, $saleCurrency).'.'],
                    ]);
                }

                $payment = Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'currency_code' => $currency,
                    'amount' => $amount,
                    'exchange_rate' => $paymentRate,
                    'amount_in_sale_currency' => $amountInSaleCurrency,
                    'reference' => $paymentData['reference'] ?? null,
                    'status' => 'confirmado',
                    'paid_at' => now(),
                    'received_by' => $user->id,
                    'notes' => $paymentData['notes'] ?? null,
                ]);

                $this->accounting->registerPayment($payment);
                $available = Money::subtractCurrency($saleCurrency, $available, $amountInSaleCurrency);
            }

            $paidTotal = $this->confirmedTotal($sale);
            $balance = Money::subtractCurrency($saleCurrency, $sale->total, $paidTotal);
            $status = Money::compare($balance, '0') <= 0 ? SaleStatus::Completada : SaleStatus::Validada;

            $sale->update([
                'paid_total' => $paidTotal,
                'balance' => $balance,
                'status' => $status,
            ]);

            AuditLogger::record(
                'sale.payment',
                $sale,
                [],
                ['paid_total' => $paidTotal, 'balance' => $balance, 'status' => $status->value],
                "Pago registrado para venta {$sale->number}",
            );

            return $sale->fresh(['payments', 'documents']);
        });
    }

    public function confirmedTotal(Sale $sale): string
    {
        $total = Payment::query()
            ->where('sale_id', $sale->id)
            ->where('status', 'confirmado')
            ->sum('amount_in_sale_currency');

        return Money::round($total, $sale->currency_code);
    }
}
