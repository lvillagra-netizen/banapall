<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'capacity_kg', 'cost_clp', 'is_active'];

    protected function casts(): array
    {
        return [
            'capacity_kg' => 'decimal:3',
            'cost_clp' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active bins.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
