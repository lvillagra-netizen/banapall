<?php

namespace App\Models;

use App\Support\RutValidator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_id_type',
        'tax_id',
        'name',
        'legal_name',
        'email',
        'phone',
        'address',
        'city',
        'region',
        'country_code',
        'currency_code',
        'credit_limit',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('legal_name', 'ilike', "%{$term}%")
                ->orWhere('tax_id', 'ilike', "%{$term}%")
                ->orWhere('email', 'ilike', "%{$term}%");
        });
    }

    public function countryLabel(): string
    {
        return (string) config("banapal.countries.{$this->country_code}.label", $this->country_code);
    }

    public function taxIdLabel(): string
    {
        return (string) config("banapal.countries.{$this->country_code}.tax_id_label", $this->tax_id_type);
    }

    public function displayTaxId(): string
    {
        if ($this->tax_id_type === 'RUT') {
            return RutValidator::formatRut($this->tax_id) ?? '—';
        }

        return $this->tax_id ?: '—';
    }
}
