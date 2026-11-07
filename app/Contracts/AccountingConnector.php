<?php

namespace App\Contracts;

use App\Models\Document;
use App\Models\Payment;

interface AccountingConnector
{
    public function registerSale(Document $document): void;

    public function registerPayment(Payment $payment): void;

    public function reverseSale(Document $document): void;
}
