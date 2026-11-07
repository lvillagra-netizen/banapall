<?php

namespace App\Exceptions;

use RuntimeException;

class SaleValidationException extends RuntimeException
{
    public function __construct(
        public array $details = [],
        string $message = 'La venta no pasó la validación.',
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->details['errors'] ?? [$this->getMessage()];
    }

    public function stockErrors(): array
    {
        return $this->details['stock'] ?? [];
    }
}
