<?php

namespace App\Services;

use App\Contracts\AccountingConnector;
use App\Contracts\SiiConnector;
use App\Enums\Currency;
use App\Enums\SaleStatus;
use App\Enums\TaxMode;
use App\Enums\ValidationStatus;
use App\Exceptions\SaleValidationException;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRates,
        private readonly SaleCalculator $calculator,
        private readonly SaleValidationService $validation,
        private readonly InventoryService $inventory,
        private readonly DocumentService $documents,
        private readonly PaymentService $payments,
        private readonly SiiConnector $sii,
        private readonly AccountingConnector $accounting,
    ) {
    }

    public function createDraft(array $data, User $seller): Sale
    {
        return DB::transaction(function () use ($data, $seller) {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
            $currency = strtoupper($data['currency_code'] ?? $customer->currency_code ?? config('banapal.base_currency', 'CLP'));
            $taxMode = TaxMode::tryFrom($data['tax_mode'] ?? '') ?? TaxMode::forCountry($customer->country_code);
            $exchangeRate = $this->exchangeRates->rateFor($currency);
            $calculation = $this->calculator->calculate($data['items'] ?? [], $currency, $taxMode, $exchangeRate);

            $sale = Sale::create([
                'customer_id' => $customer->id,
                'seller_id' => $seller->id,
                'warehouse_id' => $warehouse->id,
                'currency_code' => $currency,
                'exchange_rate' => $exchangeRate,
                'tax_mode' => $taxMode,
                'tax_rate' => $taxMode->rate(),
                'subtotal' => $calculation['totals']['subtotal'],
                'discount_total' => $calculation['totals']['discount_total'],
                'tax_total' => $calculation['totals']['tax_total'],
                'total' => $calculation['totals']['total'],
                'paid_total' => 0,
                'balance' => $calculation['totals']['total'],
                'status' => SaleStatus::Borrador,
                'validation_status' => ValidationStatus::Pendiente,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->replaceItems($sale, $calculation['items']);
            AuditLogger::record('sale.created', $sale, [], $sale->only(['customer_id', 'warehouse_id', 'currency_code', 'total']), 'Venta borrador creada');

            return $sale->load(['items', 'customer', 'warehouse']);
        });
    }

    public function updateDraft(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->isDraft()) {
                throw new SaleValidationException(['errors' => ['Solo se pueden editar ventas en borrador.']]);
            }

            $customer = Customer::query()->findOrFail($data['customer_id'] ?? $sale->customer_id);
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id'] ?? $sale->warehouse_id);
            $currency = strtoupper($data['currency_code'] ?? $sale->currency_code);
            $taxMode = TaxMode::tryFrom($data['tax_mode'] ?? '') ?? $sale->tax_mode;
            $exchangeRate = $this->exchangeRates->rateFor($currency);
            $calculation = $this->calculator->calculate($data['items'] ?? [], $currency, $taxMode, $exchangeRate);

            $sale->update([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'currency_code' => $currency,
                'exchange_rate' => $exchangeRate,
                'tax_mode' => $taxMode,
                'tax_rate' => $taxMode->rate(),
                'subtotal' => $calculation['totals']['subtotal'],
                'discount_total' => $calculation['totals']['discount_total'],
                'tax_total' => $calculation['totals']['tax_total'],
                'total' => $calculation['totals']['total'],
                'balance' => $calculation['totals']['total'],
                'notes' => $data['notes'] ?? $sale->notes,
            ]);

            $sale->items()->delete();
            $this->replaceItems($sale, $calculation['items']);
            AuditLogger::record('sale.updated', $sale, [], $sale->only(['customer_id', 'warehouse_id', 'currency_code', 'total']), 'Venta borrador actualizada');

            return $sale->fresh(['items', 'customer', 'warehouse']);
        });
    }

    public function finalize(Sale $sale, User $user, array $payments = []): Sale
    {
        $result = DB::transaction(function () use ($sale, $user, $payments) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->isDraft()) {
                throw new SaleValidationException(['errors' => ['La venta ya fue validada o anulada.']]);
            }

            $this->validation->validateOrFail($sale);
            $this->inventory->issueForSale($sale, $user);
            $document = $this->documents->issueNotaVenta($sale);

            $sale->update([
                'number' => $document->folio,
                'status' => SaleStatus::Validada,
                'validation_status' => ValidationStatus::Aprobada,
                'validation_errors' => null,
                'validated_by' => $user->id,
                'validated_at' => now(),
            ]);

            $this->sii->transmit($document);
            $this->accounting->registerSale($document->fresh());

            AuditLogger::record(
                'sale.validated',
                $sale,
                [],
                ['number' => $sale->number, 'total' => (string) $sale->total, 'currency' => $sale->currency_code],
                "Venta {$sale->number} validada y documento emitido",
            );

            if ($payments !== []) {
                $sale = $this->payments->record($sale->fresh(), $payments, $user);
            }

            return $sale->fresh(['items', 'customer', 'warehouse', 'documents', 'payments']);
        });

        return $result;
    }

    public function cancel(Sale $sale, User $user, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason) {
            $sale = Sale::query()->with(['documents', 'payments'])->lockForUpdate()->findOrFail($sale->id);

            if (! in_array($sale->status, [SaleStatus::Validada, SaleStatus::Completada], true)) {
                throw new SaleValidationException(['errors' => ['Solo se pueden anular ventas validadas o completadas.']]);
            }

            if (blank(trim($reason))) {
                throw new SaleValidationException(['errors' => ['Debe indicar el motivo de la anulación.']]);
            }

            $this->authorizeCancel($sale, $user);

            $document = $sale->notaVenta();

            $this->inventory->restoreForSale($sale, $user);

            if ($document) {
                $this->accounting->reverseSale($document);
                $this->sii->void($document, $reason);
                $this->documents->void($document);
            }

            $sale->payments()->update(['status' => 'anulado']);

            $sale->update([
                'status' => SaleStatus::Anulada,
                'balance' => 0,
                'cancellation_reason' => $reason,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            AuditLogger::record('sale.cancelled', $sale, [], ['reason' => $reason], "Venta {$sale->number} anulada");

            return $sale->fresh(['items', 'customer', 'warehouse', 'documents', 'payments']);
        });
    }

    private function replaceItems(Sale $sale, array $items): void
    {
        foreach ($items as $item) {
            SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
        }
    }

    private function authorizeCancel(Sale $sale, User $user): void
    {
        // Admin, contabilidad e inventario pueden anular cualquier venta
        if ($user->hasAnyRole(['admin', 'contabilidad', 'inventario'])) {
            return;
        }

        // Ventas con permiso de anulación grande pueden anular cualquier venta
        if ($user->can('sales.cancel_large')) {
            return;
        }

        // Umbral: solo vendedores sin permiso especial pueden anular ventas menores al monto máximo
        $maxCancelAmount = (float) Setting::get('sales.max_cancel_amount_clp', 100000);

        $totalClp = (float) $sale->total * (float) $sale->exchange_rate;

        if ($totalClp > $maxCancelAmount) {
            throw new SaleValidationException([
                'errors' => [
                    "No puedes anular ventas mayores a $" . number_format($maxCancelAmount, 0, ',', '.') . " CLP. Solicita autorización a un administrador.",
                ],
            ]);
        }
    }
}
