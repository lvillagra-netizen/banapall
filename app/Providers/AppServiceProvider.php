<?php

namespace App\Providers;

use App\Contracts\AccountingConnector;
use App\Contracts\SiiConnector;
use App\Services\SimulatedAccountingConnector;
use App\Services\SimulatedSiiConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiiConnector::class, SimulatedSiiConnector::class);
        $this->app->bind(AccountingConnector::class, SimulatedAccountingConnector::class);
    }

    public function boot(): void
    {
        //
    }
}
