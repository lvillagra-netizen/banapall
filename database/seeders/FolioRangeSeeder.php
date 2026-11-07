<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\FolioRange;
use Illuminate\Database\Seeder;

class FolioRangeSeeder extends Seeder
{
    public function run(): void
    {
        $ranges = [
            [DocumentType::NotaVenta, 'NV', 1, 5000],
            [DocumentType::Boleta, 'B', 1, 2000],
            [DocumentType::Factura, 'F', 1, 1000],
        ];

        foreach ($ranges as [$type, $prefix, $start, $end]) {
            FolioRange::query()->firstOrCreate(
                ['document_type' => $type->value, 'prefix' => $prefix, 'start_number' => $start],
                [
                    'end_number' => $end,
                    'next_number' => $start,
                    'is_active' => true,
                    'notes' => 'Rango inicial autorizado para el prototipo.',
                ],
            );
        }
    }
}
