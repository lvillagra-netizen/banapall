<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ExchangeRateService;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(ExchangeRateService $exchangeRates): View
    {
        $products = Product::query()
            ->with(['category', 'prices'])
            ->active()
            ->orderBy('name')
            ->take(6)
            ->get();

        return view('landing', [
            'products' => $products,
            'rates' => $exchangeRates->latestRates(),
            'company' => config('banapal.company'),
        ]);
    }
}
