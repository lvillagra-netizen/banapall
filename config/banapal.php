<?php

return [
    'company' => [
        'name' => env('BANAPAL_COMPANY_NAME', 'BANAPAL SpA'),
        'tax_id' => env('BANAPAL_COMPANY_TAX_ID', '76.543.210-8'),
        'address' => env('BANAPAL_COMPANY_ADDRESS', 'Av. Palta 1200, Santiago, Chile'),
        'activity' => env('BANAPAL_COMPANY_ACTIVITY', 'Packing y comercialización de paltas orgánicas'),
        'email' => env('BANAPAL_COMPANY_EMAIL', 'ventas@banapal.cl'),
        'phone' => env('BANAPAL_COMPANY_PHONE', '+56 2 2345 6789'),
        'city' => env('BANAPAL_COMPANY_CITY', 'Santiago'),
        'country' => env('BANAPAL_COMPANY_COUNTRY', 'CL'),
        'sii_resolution' => env('BANAPAL_SII_RESOLUTION', 'Resolución Ex. SII N° 80 de 2024'),
        'sii_date' => env('BANAPAL_SII_DATE', '2024-01-01'),
    ],

    'base_currency' => env('BANAPAL_BASE_CURRENCY', 'CLP'),

    'currencies' => [
        'CLP' => [
            'label' => 'Peso chileno',
            'symbol' => '$',
            'decimals' => 0,
            'locale' => 'es_CL',
        ],
        'USD' => [
            'label' => 'Dólar estadounidense',
            'symbol' => 'US$',
            'decimals' => 2,
            'locale' => 'en_US',
        ],
        'PEN' => [
            'label' => 'Sol peruano',
            'symbol' => 'S/',
            'decimals' => 2,
            'locale' => 'es_PE',
        ],
    ],

    'taxes' => [
        'iva_19' => [
            'label' => 'IVA 19% (Chile)',
            'rate' => 0.19,
        ],
        'igv_18' => [
            'label' => 'IGV 18% (Perú)',
            'rate' => 0.18,
        ],
        'export_0' => [
            'label' => 'Exportación 0%',
            'rate' => 0.0,
        ],
        'exempt' => [
            'label' => 'Exento',
            'rate' => 0.0,
        ],
    ],

    'payment_methods' => [
        'efectivo' => ['label' => 'Efectivo', 'currencies' => ['CLP', 'USD', 'PEN']],
        'transferencia' => ['label' => 'Transferencia bancaria', 'currencies' => ['CLP', 'USD', 'PEN']],
        'tarjeta' => ['label' => 'Tarjeta (simulada)', 'currencies' => ['CLP', 'USD', 'PEN']],
        'credito' => ['label' => 'Crédito', 'currencies' => ['CLP', 'USD', 'PEN']],
        'cheque_fecha' => ['label' => 'Cheque a Fecha', 'currencies' => ['CLP', 'USD', 'PEN']],
        'yape' => ['label' => 'Yape (Perú)', 'currencies' => ['PEN']],
        'plin' => ['label' => 'Plin (Perú)', 'currencies' => ['PEN']],
        'otro' => ['label' => 'Otro medio', 'currencies' => ['CLP', 'USD', 'PEN']],
    ],

    'document_types' => [
        'nota_venta' => [
            'label' => 'Nota de Venta',
            'prefix' => 'NV',
            'default' => true,
        ],
        'boleta' => [
            'label' => 'Boleta',
            'prefix' => 'B',
            'default' => false,
        ],
        'factura' => [
            'label' => 'Factura',
            'prefix' => 'F',
            'default' => false,
        ],
    ],

    'countries' => [
        'CL' => ['label' => 'Chile', 'currency' => 'CLP', 'tax' => 'iva_19', 'tax_id_label' => 'RUT'],
        'PE' => ['label' => 'Perú', 'currency' => 'PEN', 'tax' => 'igv_18', 'tax_id_label' => 'RUC'],
        'US' => ['label' => 'Estados Unidos', 'currency' => 'USD', 'tax' => 'export_0', 'tax_id_label' => 'Tax ID'],
        'OT' => ['label' => 'Otro país', 'currency' => 'USD', 'tax' => 'export_0', 'tax_id_label' => 'Documento'],
    ],

    'prices_include_tax' => false,
    'default_country' => 'CL',
    'pagination' => 12,
];
