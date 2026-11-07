<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'currency_code', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
