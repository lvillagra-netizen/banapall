<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Chamber;

class Stock extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['warehouse_id', 'chamber_id', 'product_id', 'quantity', 'reserved'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reserved' => 'decimal:3',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function chamber(): BelongsTo
    {
        return $this->belongsTo(Chamber::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
