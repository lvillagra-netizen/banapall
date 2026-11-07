<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'method',
        'currency_code',
        'amount',
        'exchange_rate',
        'amount_in_sale_currency',
        'reference',
        'status',
        'paid_at',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'amount_in_sale_currency' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
