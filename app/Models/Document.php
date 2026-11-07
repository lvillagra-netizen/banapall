<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\SiiStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'type',
        'folio',
        'status',
        'issued_at',
        'customer_snapshot',
        'totals_snapshot',
        'tax_snapshot',
        'pdf_path',
        'xml_payload',
        'sii_status',
        'accounting_status',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'sii_status' => SiiStatus::class,
            'issued_at' => 'datetime',
            'customer_snapshot' => 'array',
            'totals_snapshot' => 'array',
            'tax_snapshot' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class);
    }

    public function displayName(): string
    {
        return $this->type->label().' '.$this->folio;
    }
}
