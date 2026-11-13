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
    ) {}

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
            $available = Money::subtractCurrency($saleCurrency, $sale->total, $this->confirmedTotal($sale));

            foreach ($payments as $paymentData) {
                $attributes = $this->buildPayment($sale, $paymentData, $available);

                $payment = Payment::create($attributes + [
                    'sale_id' => $sale->id,
                    'status' => 'confirmado',
                    'paid_at' => now(),
                    'received_by' => $user->id,
                ]);

                $this->accounting->registerPayment($payment);
                $available = Money::subtractCurrency($saleCurrency, $available, $attributes['amount_in_sale_currency']);
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

    /**
     * Guarda los pagos capturados en un borrador con estado "pendiente".
     * Se confirman recién al validar la venta.
     */
    public function recordDraft(Sale $sale, array $payments, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $payments, $user) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->isDraft()) {
                throw new SaleValidationException(['errors' => ['Solo se pueden registrar pagos sobre ventas en borrador.']]);
            }

            $pending = Payment::query()->where('sale_id', $sale->id)->where('status', 'pendiente');

            if ($payments === [] && ! $pending->exists()) {
                return $sale->fresh(['payments']);
            }

            $pending->delete();

            $saleCurrency = $sale->currency_code;
            $available = $sale->total;

            foreach ($payments as $paymentData) {
                $attributes = $this->buildPayment($sale, $paymentData, $available);

                Payment::create($attributes + [
                    'sale_id' => $sale->id,
                    'status' => 'pendiente',
                    'paid_at' => null,
                    'received_by' => $user->id,
                ]);

                $available = Money::subtractCurrency($saleCurrency, $available, $attributes['amount_in_sale_currency']);
            }

            $paidTotal = $this->pendingTotal($sale);
            $balance = Money::subtractCurrency($saleCurrency, $sale->total, $paidTotal);

            $sale->update([
                'paid_total' => $paidTotal,
                'balance' => $balance,
            ]);

            AuditLogger::record(
                'sale.payment.draft',
                $sale,
                [],
                ['paid_total' => $paidTotal, 'balance' => $balance],
                'Pagos de borrador registrados',
            );

            return $sale->fresh(['payments']);
        });
    }

    /**
     * Descarta los pagos "pendientes" de un borrador (el formulario vuelve a ser la fuente de verdad).
     */
    public function discardDraftPayments(Sale $sale): void
    {
        Payment::query()
            ->where('sale_id', $sale->id)
            ->where('status', 'pendiente')
            ->delete();
    }

    /**
     * Confirma los pagos pendientes de un borrador recién validado.
     */
    public function confirmPending(Sale $sale, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $user) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            $pending = Payment::query()
                ->where('sale_id', $sale->id)
                ->where('status', 'pendiente')
                ->get();

            foreach ($pending as $payment) {
                $payment->update([
                    'status' => 'confirmado',
                    'paid_at' => now(),
                    'received_by' => $user->id,
                ]);

                $this->accounting->registerPayment($payment);
            }

            $paidTotal = $this->confirmedTotal($sale);
            $balance = Money::subtractCurrency($sale->currency_code, $sale->total, $paidTotal);
            $status = Money::compare($balance, '0') <= 0 ? SaleStatus::Completada : SaleStatus::Validada;

            $sale->update([
                'paid_total' => $paidTotal,
                'balance' => $balance,
                'status' => $status,
            ]);

            if ($pending->isNotEmpty()) {
                AuditLogger::record(
                    'sale.payment',
                    $sale,
                    [],
                    ['paid_total' => $paidTotal, 'balance' => $balance, 'status' => $status->value],
                    'Pagos pendientes del borrador confirmados al validar',
                );
            }

            return $sale->fresh(['payments']);
        });
    }

    public function confirmedTotal(Sale $sale): string
    {
        return $this->totalFor($sale, ['confirmado']);
    }

    public function pendingTotal(Sale $sale): string
    {
        return $this->totalFor($sale, ['pendiente']);
    }

    private function totalFor(Sale $sale, array $statuses): string
    {
        $total = Payment::query()
            ->where('sale_id', $sale->id)
            ->whereIn('status', $statuses)
            ->sum('amount_in_sale_currency');

        return Money::round($total, $sale->currency_code);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayment(Sale $sale, array $paymentData, string $available): array
    {
        $method = PaymentMethod::tryFrom((string) ($paymentData['method'] ?? ''));

        if (! $method) {
            throw new SaleValidationException(['errors' => ['El medio de pago seleccionado no es válido.']]);
        }

        $currency = strtoupper((string) ($paymentData['currency_code'] ?? $sale->currency_code));
        $amount = Money::round($paymentData['amount'] ?? 0, $currency);

        if (Money::compare($amount, '0') <= 0) {
            throw new SaleValidationException(['errors' => ['El monto de cada pago debe ser mayor que cero.']]);
        }

        if (! in_array($currency, $method->allowedCurrencies(), true)) {
            throw new SaleValidationException(['errors' => ["{$method->label()} no admite pagos en {$currency}."]]);
        }

        $paymentRate = $this->exchangeRates->rateFor($currency);

        if ($currency === $sale->currency_code) {
            $amountInSaleCurrency = $amount;
        } else {
            $amountClp = Money::multiply($amount, $paymentRate);
            $amountInSaleCurrency = $sale->currency_code === 'CLP'
                ? Money::round($amountClp, 'CLP')
                : Money::round(Money::divide($amountClp, (string) $sale->exchange_rate), $sale->currency_code);
        }

        if (Money::compare($amountInSaleCurrency, $available) > 0) {
            throw new SaleValidationException([
                'errors' => ['El pago excede el saldo pendiente de '.Money::format($available, $sale->currency_code).'.'],
            ]);
        }

        return [
            'method' => $method,
            'currency_code' => $currency,
            'amount' => $amount,
            'exchange_rate' => $paymentRate,
            'amount_in_sale_currency' => $amountInSaleCurrency,
            'reference' => $paymentData['reference'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
        ];
    }
}
