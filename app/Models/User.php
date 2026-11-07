<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'phone',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function roleLabel(): string
    {
        return $this->roles->first()?->name ? ucfirst($this->roles->first()->name) : 'Sin rol';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function homeRoute(): string
    {
        return match (true) {
            $this->hasRole('admin') => route('admin.dashboard'),
            $this->hasRole('ventas') => route('pos.index'),
            $this->hasRole('inventario') => route('inventory.index'),
            $this->hasRole('contabilidad') => route('billing.documents'),
            default => route('landing'),
        };
    }
}
