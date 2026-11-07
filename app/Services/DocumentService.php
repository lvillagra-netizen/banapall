<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\SiiStatus;
use App\Models\Document;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function __construct(
        private readonly FolioService $folios,
        private readonly NotaVentaPdf $pdf,
    ) {
    }

    public function issueNotaVenta(Sale $sale): Document
    {
        return DB::transaction(function () use ($sale) {
            $existing = Document::query()
                ->where('sale_id', $sale->id)
                ->where('type', DocumentType::NotaVenta->value)
                ->where('status', DocumentStatus::Emitido->value)
                ->first();

            if ($existing) {
                return $existing;
            }

            $sale->loadMissing(['customer', 'seller', 'warehouse', 'items']);
            $folio = $this->folios->next(DocumentType::NotaVenta);

            $document = Document::create([
                'sale_id' => $sale->id,
                'type' => DocumentType::NotaVenta,
                'folio' => $folio['folio'],
                'status' => DocumentStatus::Emitido,
                'issued_at' => now(),
                'customer_snapshot' => [
                    'name' => $sale->customer->name,
                    'legal_name' => $sale->customer->legal_name,
                    'tax_id' => $sale->customer->tax_id,
                    'tax_id_type' => $sale->customer->tax_id_type,
                    'address' => $sale->customer->address,
                    'city' => $sale->customer->city,
                    'country_code' => $sale->customer->country_code,
                    'email' => $sale->customer->email,
                    'phone' => $sale->customer->phone,
                ],
                'totals_snapshot' => [
                    'currency_code' => $sale->currency_code,
                    'exchange_rate' => (string) $sale->exchange_rate,
                    'subtotal' => (string) $sale->subtotal,
                    'discount_total' => (string) $sale->discount_total,
                    'tax_total' => (string) $sale->tax_total,
                    'total' => (string) $sale->total,
                ],
                'tax_snapshot' => [
                    'mode' => $sale->tax_mode->value,
                    'label' => $sale->tax_mode->label(),
                    'rate' => (string) $sale->tax_rate,
                ],
                'sii_status' => SiiStatus::Simulado,
                'accounting_status' => 'pendiente',
            ]);

            $path = $this->pdf->generate($document);

            if ($path) {
                $document->updateQuietly(['pdf_path' => $path]);
            }

            return $document->fresh();
        });
    }

    public function void(Document $document): void
    {
        $document->update([
            'status' => DocumentStatus::Anulado,
            'sii_status' => SiiStatus::Anulado,
        ]);
    }
}
