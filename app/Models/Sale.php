<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\TaxMode;
use App\Enums\ValidationStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'customer_id',
        'seller_id',
        'warehouse_id',
        'currency_code',
        'exchange_rate',
        'tax_mode',
        'tax_rate',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_total',
        'balance',
        'status',
        'validation_status',
        'validation_errors',
        'validated_by',
        'validated_at',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
            'tax_mode' => TaxMode::class,
            'tax_rate' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => SaleStatus::class,
            'validation_status' => ValidationStatus::class,
            'validation_errors' => 'array',
            'validated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notaVenta(): ?Document
    {
        return $this->documents->firstWhere('type', \App\Enums\DocumentType::NotaVenta);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasAnyRole(['admin', 'contabilidad'])) {
            return $query;
        }

        return $query->where('seller_id', $user->id);
    }

    public function isDraft(): bool
    {
        return $this->status === SaleStatus::Borrador;
    }

    public function isCancelled(): bool
    {
        return $this->status === SaleStatus::Anulada;
    }

    public function isPaid(): bool
    {
        return Money::compare($this->balance, '0') <= 0;
    }

    public function paymentStatus(): PaymentStatus
    {
        if ($this->isCancelled()) {
            return PaymentStatus::Pendiente;
        }

        if (Money::compare($this->paid_total, $this->total) >= 0) {
            return PaymentStatus::Pagado;
        }

        if (Money::compare($this->paid_total, '0') > 0) {
            return PaymentStatus::Parcial;
        }

        return PaymentStatus::Pendiente;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }
}
