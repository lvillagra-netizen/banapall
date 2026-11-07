<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FolioRange extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'prefix',
        'start_number',
        'end_number',
        'next_number',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'start_number' => 'integer',
            'end_number' => 'integer',
            'next_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasAvailability(): bool
    {
        return $this->is_active && $this->next_number <= $this->end_number;
    }

    public function remaining(): int
    {
        return max(0, $this->end_number - $this->next_number + 1);
    }

    public function formattedNext(): string
    {
        return sprintf('%s-%06d', $this->prefix, $this->next_number);
    }
}
