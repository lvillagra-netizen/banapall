<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\AccountingEntry;
use App\Models\Document;
use App\Models\FolioRange;
use App\Services\FolioService;
use App\Services\NotaVentaPdf;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function documents(Request $request): View
    {
        $query = Document::query()->with(['sale.customer', 'sale.seller'])->latest('issued_at');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'ilike', "%{$search}%")
                    ->orWhereHas('sale.customer', fn ($customer) => $customer->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('issued_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('issued_at', '<=', $to);
        }

        return view('billing.documents', [
            'documents' => $query->paginate(20)->withQueryString(),
            'types' => DocumentType::cases(),
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    public function folios(FolioService $folios): View
    {
        return view('billing.folios', [
            'stats' => $folios->stats(),
            'types' => DocumentType::cases(),
            'ranges' => FolioRange::query()->orderBy('document_type')->orderBy('id')->get(),
        ]);
    }

    public function storeFolio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:nota_venta,boleta,factura'],
            'prefix' => ['required', 'string', 'max:10'],
            'start_number' => ['required', 'integer', 'min:1'],
            'end_number' => ['required', 'integer', 'gte:start_number'],
            'next_number' => ['required', 'integer', 'gte:start_number', 'lte:end_number'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $range = FolioRange::create(array_merge($data, ['is_active' => true]));
        AuditLogger::record('folio.created', $range, [], $range->only(['document_type', 'prefix', 'start_number', 'end_number']), 'Rango de folios creado');

        return redirect()->route('billing.folios')->with('success', 'Rango de folios creado correctamente.');
    }

    public function updateFolio(Request $request, FolioRange $range): RedirectResponse
    {
        $data = $request->validate([
            'end_number' => ['required', 'integer', 'gte:'.$range->next_number],
            'next_number' => ['required', 'integer', 'gte:'.$range->start_number, 'lte:end_number'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $old = $range->only(['end_number', 'next_number', 'is_active']);
        $range->update([
            'end_number' => $data['end_number'],
            'next_number' => $data['next_number'],
            'is_active' => $request->boolean('is_active'),
            'notes' => $data['notes'] ?? $range->notes,
        ]);
        AuditLogger::record('folio.updated', $range, $old, $range->only(['end_number', 'next_number', 'is_active']), 'Rango de folios actualizado');

        return redirect()->route('billing.folios')->with('success', 'Rango de folios actualizado.');
    }

    public function accounting(Request $request): View
    {
        $query = AccountingEntry::query()->with(['sale.customer', 'document'])->latest('entry_date')->latest('id');

        if ($from = $request->date('from')) {
            $query->whereDate('entry_date', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('entry_date', '<=', $to);
        }

        if ($account = $request->string('account')->trim()->toString()) {
            $query->where(function ($q) use ($account) {
                $q->where('account_code', 'ilike', "%{$account}%")
                    ->orWhere('account_name', 'ilike', "%{$account}%");
            });
        }

        $totals = clone $query;
        $debit = (clone $totals)->sum('debit');
        $credit = (clone $totals)->sum('credit');
        $clp = (clone $totals)->sum('amount_clp');

        return view('billing.accounting', [
            'entries' => $query->paginate(30)->withQueryString(),
            'debit' => $debit,
            'credit' => $credit,
            'clp' => $clp,
        ]);
    }

    public function download(Document $document, NotaVentaPdf $pdf)
    {
        return $pdf->download($document);
    }
}
