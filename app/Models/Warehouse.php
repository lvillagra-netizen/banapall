<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function chambers(): HasMany
    {
        return $this->hasMany(Chamber::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
