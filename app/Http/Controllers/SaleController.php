<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Exceptions\SaleValidationException;
use App\Models\AccountingEntry;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sale::query()
            ->with(['customer', 'seller'])
            ->forUser($request->user())
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($currency = $request->string('currency')->toString()) {
            $query->where('currency_code', $currency);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return view('sales.index', [
            'sales' => $query->paginate(15)->withQueryString(),
            'statuses' => SaleStatus::cases(),
        ]);
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeView($request->user(), $sale);

        $sale->load(['customer', 'seller', 'warehouse', 'validator', 'canceller', 'items.product', 'payments.receiver', 'documents']);
        $document = $sale->notaVenta();

        return view('sales.show', [
            'sale' => $sale,
            'document' => $document,
            'paymentMethods' => PaymentMethod::cases(),
            'accountingEntries' => $document
                ? AccountingEntry::query()->where('document_id', $document->id)->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function validateSale(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        $this->authorizeView($request->user(), $sale);

        try {
            $sale = $sales->finalize($sale, $request->user());

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', "Venta {$sale->number} validada y nota de venta emitida.");
        } catch (SaleValidationException $exception) {
            if ($sale->isDraft()) {
                $sale->update(['validation_errors' => $exception->errors()]);
            }

            return redirect()
                ->route('sales.show', $sale)
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('error', $exception->getMessage());
        }
    }

    public function pay(Request $request, Sale $sale, PaymentService $payments): RedirectResponse
    {
        $this->authorizeView($request->user(), $sale);

        $data = $request->validate([
            'method' => ['required', 'in:efectivo,transferencia,tarjeta,yape,plin,otro'],
            'currency_code' => ['required', 'in:CLP,USD,PEN'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payments->record($sale, [$data], $request->user());

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', 'Pago registrado correctamente.');
        } catch (SaleValidationException $exception) {
            return redirect()
                ->route('sales.show', $sale)
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('error', $exception->getMessage());
        }
    }

    public function cancel(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $sales->cancel($sale, $request->user(), $data['reason']);

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', 'Venta anulada. El stock y la contabilidad fueron reversados.');
        } catch (SaleValidationException $exception) {
            return redirect()
                ->route('sales.show', $sale)
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('error', $exception->getMessage());
        }
    }

    private function authorizeView(User $user, Sale $sale): void
    {
        if ($user->hasAnyRole(['admin', 'contabilidad', 'inventario'])) {
            return;
        }

        abort_unless($sale->seller_id === $user->id, 403, 'No tienes acceso a esta venta.');
    }
}
