<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\ExchangeRateService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(ExchangeRateService $exchangeRates): View
    {
        return view('settings.index', [
            'settings' => Setting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group'),
            'rates' => $exchangeRates->latestRates(),
            'rateHistory' => ExchangeRate::query()->orderByDesc('effective_date')->orderBy('currency_code')->take(30)->get(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:3000'],
        ]);

        $records = Setting::query()->whereIn('key', array_keys($request->input('settings', [])))->get();

        foreach ($records as $setting) {
            $value = $request->input("settings.{$setting->key}");

            if ($setting->type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            $setting->update(['value' => $value]);
        }

        AuditLogger::record('settings.updated', null, [], ['keys' => $records->pluck('key')->all()], 'Configuración actualizada');

        return redirect()->route('settings.index')->with('success', 'Configuración actualizada correctamente.');
    }

    public function storeRate(Request $request, ExchangeRateService $exchangeRates): RedirectResponse
    {
        $data = $request->validate([
            'currency_code' => ['required', 'in:USD,PEN'],
            'rate_to_clp' => ['required', 'numeric', 'gt:0'],
            'effective_date' => ['required', 'date'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        $rate = $exchangeRates->store($data['currency_code'], $data['rate_to_clp'], $data['effective_date'], $data['source'] ?? 'Manual');
        AuditLogger::record('exchange_rate.updated', $rate, [], $rate->only(['currency_code', 'rate_to_clp', 'effective_date']), 'Tipo de cambio registrado');

        return redirect()->route('settings.index')->with('success', 'Tipo de cambio registrado correctamente.');
    }
}
