<?php

namespace App\Services;

use App\Contracts\AccountingConnector;
use App\Models\AccountingEntry;
use App\Models\Document;
use App\Models\Payment;
use App\Support\Money;

class SimulatedAccountingConnector implements AccountingConnector
{
    public function registerSale(Document $document): void
    {
        if (AccountingEntry::query()
            ->where('document_id', $document->id)
            ->where('description', 'like', 'Venta%')
            ->exists()) {
            return;
        }

        $document->loadMissing('sale');
        $sale = $document->sale;
        $exchangeRate = (string) $sale->exchange_rate;
        $netRevenue = Money::subtractCurrency($sale->currency_code, $sale->subtotal, $sale->discount_total);

        $entries = [
            ['1101', 'Cuentas por cobrar', 'Venta '.$document->folio, $sale->total, '0'],
            ['4101', 'Ingresos por ventas', 'Venta '.$document->folio, '0', $netRevenue],
            ['2101', 'IVA/IGV débito fiscal', 'Venta '.$document->folio, '0', $sale->tax_total],
        ];

        foreach ($entries as [$code, $name, $description, $debit, $credit]) {
            if (Money::isZero($debit) && Money::isZero($credit)) {
                continue;
            }

            $this->entry($sale->id, $document->id, $code, $name, $description, $debit, $credit, $sale->currency_code, $exchangeRate);
        }

        $document->update(['accounting_status' => 'registrado']);
    }

    public function registerPayment(Payment $payment): void
    {
        $payment->loadMissing('sale');
        $sale = $payment->sale;
        $description = 'Pago venta '.($sale->number ?? $sale->id).' / '.$payment->method->value;

        $this->entry(
            $sale->id,
            $sale->notaVenta()?->id,
            '1102',
            'Caja y bancos',
            $description,
            $payment->amount_in_sale_currency,
            '0',
            $sale->currency_code,
            (string) $sale->exchange_rate,
        );

        $this->entry(
            $sale->id,
            $sale->notaVenta()?->id,
            '1101',
            'Cuentas por cobrar',
            $description,
            '0',
            $payment->amount_in_sale_currency,
            $sale->currency_code,
            (string) $sale->exchange_rate,
        );
    }

    public function reverseSale(Document $document): void
    {
        if (AccountingEntry::query()
            ->where('document_id', $document->id)
            ->where('description', 'like', 'Reversa%')
            ->exists()) {
            return;
        }

        $document->loadMissing('sale');
        $sale = $document->sale;
        $entries = AccountingEntry::query()
            ->where('sale_id', $sale->id)
            ->where('description', 'not like', 'Reversa%')
            ->get();

        foreach ($entries as $entry) {
            $this->entry(
                $entry->sale_id,
                $entry->document_id,
                $entry->account_code,
                $entry->account_name,
                'Reversa: '.$entry->description,
                $entry->credit,
                $entry->debit,
                $entry->currency_code,
                (string) $sale->exchange_rate,
            );
        }

        $document->update(['accounting_status' => 'reversado']);
    }

    private function entry(
        ?int $saleId,
        ?int $documentId,
        string $code,
        string $name,
        string $description,
        mixed $debit,
        mixed $credit,
        string $currency,
        string $exchangeRate,
    ): void {
        $debit = Money::round($debit, $currency);
        $credit = Money::round($credit, $currency);
        $amount = Money::add($debit, $credit);
        $amountClp = Money::round(Money::multiply($amount, $exchangeRate), 'CLP');

        AccountingEntry::create([
            'sale_id' => $saleId,
            'document_id' => $documentId,
            'account_code' => $code,
            'account_name' => $name,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'currency_code' => $currency,
            'amount_clp' => $amountClp,
            'entry_date' => today(),
        ]);
    }
}
