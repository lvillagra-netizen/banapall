<?php

namespace App\Models;

use App\Services\PriceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'caliber_id',
        'sku',
        'name',
        'slug',
        'variety',
        'description',
        'unit',
        'package_weight_kg',
        'is_organic',
        'certifier',
        'certification_code',
        'min_stock',
        'cost_clp',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'package_weight_kg' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'cost_clp' => 'decimal:2',
            'is_organic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name).'-'.strtolower($product->sku);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function caliber(): BelongsTo
    {
        return $this->belongsTo(Caliber::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function priceFor(string $currency): string
    {
        return app(PriceService::class)->priceFor($this, $currency);
    }

    public function stockIn(int $warehouseId): string
    {
        return (string) ($this->stocks->firstWhere('warehouse_id', $warehouseId)?->quantity ?? '0');
    }

    public function imageUrl(): string
    {
        if (blank($this->image_path)) {
            return asset('images/products/avocado.svg');
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://', '/'])) {
            return $this->image_path;
        }

        if (Str::startsWith($this->image_path, 'images/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function isLowStock(int $warehouseId): bool
    {
        return (float) $this->stockIn($warehouseId) <= (float) $this->min_stock;
    }
}
