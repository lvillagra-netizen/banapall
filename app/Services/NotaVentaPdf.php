<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NotaVentaPdf
{
    public function generate(Document $document): ?string
    {
        try {
            $document->loadMissing(['sale.customer', 'sale.seller', 'sale.warehouse', 'sale.items']);
            $sale = $document->sale;

            $pdf = Pdf::loadView('pdf.nota-venta', [
                'document' => $document,
                'sale' => $sale,
                'company' => config('banapal.company'),
            ])->setPaper('a4');

            $path = sprintf('notas/%s/%s.pdf', $sale->created_at->format('Y'), $document->folio);
            Storage::disk('public')->put($path, $pdf->output());
            $document->updateQuietly(['pdf_path' => $path]);

            return $path;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function download(Document $document)
    {
        if ($document->pdf_path && Storage::disk('public')->exists($document->pdf_path)) {
            return Storage::disk('public')->download($document->pdf_path, "{$document->folio}.pdf");
        }

        abort(404, 'El PDF del documento no está disponible.');
    }
}
