<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\AuditLogger;
use App\Support\RutValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->withCount('sales')->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->search($search);
        }

        if ($country = $request->string('country')->toString()) {
            $query->where('country_code', $country);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return view('customers.index', [
            'customers' => $query->paginate(15)->withQueryString(),
            'countries' => config('banapal.countries'),
        ]);
    }

    public function create(): View
    {
        return view('customers.form', [
            'customer' => new Customer(['country_code' => 'CL', 'currency_code' => 'CLP', 'tax_id_type' => 'RUT', 'is_active' => true]),
            'countries' => config('banapal.countries'),
            'currencies' => config('banapal.currencies'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $customer = Customer::create($data);
        AuditLogger::record('customer.created', $customer, [], $customer->only(['name', 'tax_id', 'country_code']), 'Cliente creado');

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['sales' => fn ($query) => $query->latest()->limit(10)]);

        return view('customers.show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', [
            'customer' => $customer,
            'countries' => config('banapal.countries'),
            'currencies' => config('banapal.currencies'),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request, $customer);
        $old = $customer->only(['name', 'tax_id', 'email', 'is_active']);
        $customer->update($data);
        AuditLogger::record('customer.updated', $customer, $old, $customer->only(['name', 'tax_id', 'email', 'is_active']), 'Cliente actualizado');

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();
        AuditLogger::record('customer.deleted', $customer, $customer->only(['name', 'tax_id']), [], 'Cliente desactivado');

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente eliminado. Sus ventas históricas se conservan.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'tax_id_type' => ['required', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'in:CL,PE,US,OT'],
            'currency_code' => ['required', 'in:CLP,USD,PEN'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['tax_id'] = $this->normalizeTaxId($data['tax_id_type'], $data['tax_id'] ?? null);

        if (! RutValidator::isValid($data['tax_id_type'], $data['tax_id'])) {
            throw ValidationException::withMessages([
                'tax_id' => "El {$data['tax_id_type']} ingresado no es válido.",
            ]);
        }

        if ($data['tax_id']) {
            $exists = Customer::withTrashed()
                ->where('tax_id', $data['tax_id'])
                ->when($customer, fn ($query) => $query->whereKeyNot($customer->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'tax_id' => 'Ya existe un cliente con este documento tributario.',
                ]);
            }
        }

        return $data;
    }

    private function normalizeTaxId(string $type, ?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return match (strtoupper($type)) {
            'RUT' => RutValidator::formatRut($value),
            'RUC' => preg_replace('/\D/', '', $value),
            default => mb_strtoupper(trim($value)),
        };
    }
}
