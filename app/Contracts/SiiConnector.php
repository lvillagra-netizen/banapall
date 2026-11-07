<?php

namespace App\Contracts;

use App\Models\Document;

interface SiiConnector
{
    public function transmit(Document $document): string;

    public function void(Document $document, string $reason): void;
}
