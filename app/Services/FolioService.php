<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\FolioRange;
use RuntimeException;

class FolioService
{
    public function next(DocumentType $type, ?string $prefix = null): array
    {
        $prefix ??= $type->prefix();

        $range = FolioRange::query()
            ->where('document_type', $type->value)
            ->where('prefix', $prefix)
            ->where('is_active', true)
            ->whereColumn('next_number', '<=', 'end_number')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $range) {
            throw new RuntimeException("No hay folios disponibles para {$type->label()} ({$prefix}). Configure un nuevo rango de folios.");
        }

        $number = (int) $range->next_number;
        $range->increment('next_number');

        return [
            'folio' => sprintf('%s-%06d', $prefix, $number),
            'number' => $number,
            'range_id' => $range->id,
        ];
    }

    public function available(DocumentType $type): int
    {
        return (int) FolioRange::query()
            ->where('document_type', $type->value)
            ->where('is_active', true)
            ->get()
            ->sum(fn (FolioRange $range) => $range->remaining());
    }

    public function stats(): array
    {
        return collect(DocumentType::cases())
            ->map(fn (DocumentType $type) => [
                'type' => $type,
                'available' => $this->available($type),
                'ranges' => FolioRange::query()->where('document_type', $type->value)->orderBy('id')->get(),
            ])
            ->all();
    }
}
