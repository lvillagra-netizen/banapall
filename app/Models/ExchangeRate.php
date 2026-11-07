<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeRate extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['currency_code', 'rate_to_clp', 'effective_date', 'source'];

    protected function casts(): array
    {
        return [
            'rate_to_clp' => 'decimal:6',
            'effective_date' => 'date',
        ];
    }

    public function scopeLatestFor($query, string $currency, ?string $date = null)
    {
        return $query->where('currency_code', $currency)
            ->whereDate('effective_date', '<=', $date ?? today())
            ->orderByDesc('effective_date');
    }
}
