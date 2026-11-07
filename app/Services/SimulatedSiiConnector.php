<?php

namespace App\Services;

use App\Contracts\SiiConnector;
use App\Enums\SiiStatus;
use App\Models\Document;

class SimulatedSiiConnector implements SiiConnector
{
    public function transmit(Document $document): string
    {
        $document->loadMissing('sale.customer');

        $tracking = 'SII-SIM-'.$document->folio.'-'.now()->format('YmdHis');
        $xml = $this->buildXml($document, $tracking);

        $document->update([
            'xml_payload' => $xml,
            'sii_status' => SiiStatus::Simulado,
        ]);

        return $tracking;
    }

    public function void(Document $document, string $reason): void
    {
        $document->update(['sii_status' => SiiStatus::Anulado]);
    }

    private function buildXml(Document $document, string $tracking): string
    {
        $sale = $document->sale;
        $customer = $document->customer_snapshot ?? [];

        return implode('', [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<DTE version="1.0">',
            '<Documento>',
            '<TipoDTE>'.e($document->type->value).'</TipoDTE>',
            '<Folio>'.e($document->folio).'</Folio>',
            '<FechaEmision>'.e($document->issued_at?->format('Y-m-d')).'</FechaEmision>',
            '<Emisor><RUT>'.e(config('banapal.company.tax_id')).'</RUT><RazonSocial>'.e(config('banapal.company.name')).'</RazonSocial></Emisor>',
            '<Receptor><RUT>'.e($customer['tax_id'] ?? '').'</RUT><RazonSocial>'.e($customer['name'] ?? '').'</RazonSocial></Receptor>',
            '<Moneda>'.e($sale->currency_code).'</Moneda>',
            '<MontoNeto>'.e($sale->subtotal).'</MontoNeto>',
            '<MontoIVA>'.e($sale->tax_total).'</MontoIVA>',
            '<MontoTotal>'.e($sale->total).'</MontoTotal>',
            '<Estado>simulado</Estado>',
            '<TrackId>'.e($tracking).'</TrackId>',
            '</Documento>',
            '</DTE>',
        ]);
    }
}
