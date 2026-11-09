# PROGRESS.md

## Última tarea completada
- **Actualización de pie de página**: Se actualizó el texto del pie en [layouts/app.blade.php](file:///c:/SITIOS/banapal/resources/views/layouts/app.blade.php#L191) de `"BANAPALL SpA · Prototipo con SII y contabilidad simulados."` a `"BANAPALL SpA · Prototipo"`. Sincronizado a `packing` y limpiada la caché de vistas en Docker.
- **Implementación de observaciones en Módulo de Ventas y PDF**:
  1. **Confirmación antes de emitir**: Al presionar "Validar y emitir" en el Módulo de Ventas, se despliega un modal con el resumen de la venta (cliente, total a cobrar, abono y saldo pendiente) solicitando confirmación del usuario antes de procesar y descontar stock.
  2. **Reemplazo de texto a "Módulo de Ventas"**: Se renombraron todas las referencias a "Punto de Venta" / "Punto de venta" por "Módulo de Ventas" en títulos, migas de pan (eyebrows), textos descriptivos, landing y configuración.
  3. **Selector y validación de tipo de pago**: En la sección Pagos del POS (y en el detalle de la venta), el selector de método de pago ahora muestra por defecto la opción `"Seleccione el tipo de Pago..."` (con valor vacío) e incluye validación visual y lógica que impide continuar o emitir pagos con montos sin un tipo de pago seleccionado.
  4. **Ajustes en PDF de Nota de Venta**: Se eliminó la etiqueta `"EMITIDO · SII SIMULADO"` de la caja de folio y cabecera del documento, y se renombró la cabecera de la columna `"Precio"` a `"Precio Unitario"`.
  - Assets compilados con Vite (`npm run build`) y sincronizados a `banapal`, cachés optimizadas y de vistas limpiadas en Docker (`banapal-app`). Pruebas unitarias/features y E2E verificadas con éxito.
- **Corrección de dropdown / combo box de Producto en POS**: Al presionar el botón "Agregar" (o agregar un producto), el menú desplegable de resultados de búsqueda de producto ya no queda abierto cubriendo el producto agregado en el carrito. Se forzó `productOpen = false` de forma sincrónica y en `$nextTick`, y se aseguró `x-cloak` y `@click.outside` en el componente. Los assets se compilaron (`npm run build`) y sincronizaron a `banapal`, limpiando la caché de vistas en Docker.
- **Opción "Contabilidad" eliminada del sidebar**: se removió el enlace `billing.accounting` (bloque `@can('accounting.view')`) de `resources/views/layouts/app.blade.php` y `accounting.view` salió del `@canany` de la sección "Administración". La ruta/vista `billing/accounting` sigue accesible directamente por URL; solo se oculta del menú. Aplicado en `banapal` y sincronizado a `packing`; vistas compiladas limpiadas (`php artisan view:clear`) en `banapal-app`. Verificado en UI logueada (0 menciones de "Contabilidad" en el dashboard) y smoke E2E: 6/7 + reintento del que falló por timeout → **7/7**.
- **Corrección de layout de entrada en Punto de Venta (POS)**: Se corrigió la superposición/amontonamiento entre los campos Producto y Cantidad. La grilla de 5 columnas en una sola fila (que aplastaba Producto a ~50px) se reorganizó en dos filas ergonómicas: Fila 1 para Producto (buscador amplio con `1fr`) y Cantidad (`7rem`), y Fila 2 para Cámara (`1fr`), Bin (`1fr`) y botón Agregar (`w-32`). Recompilados los assets con `npm run build`, sincronizados a `C:\SITIOS\banapal` y limpiada la caché de vistas en Docker.
- **Pruebas E2E de los nuevos flujos** (`tests/e2e/nuevos-flujos.spec.ts`,6 tests): POS con cámara/bin + borrador, validación de stock en cliente, stock por cámara en inventario, aprobación de movimientos, validación+anulación de venta desde POS, y límite de anulación de vendedores ($100.000 CLP). Suite completa **20/20 en verde** (smoke7 + fase4 7 + nuevos flujos6).
- **Ajuste de UI de cámara/bin en POS**: fila de entrada con grilla de5 columnas (producto, cantidad, cámara, bin, agregar); `bins` ahora se inyecta en `window.__POS_CONFIG__` (el dropdown Bin siempre salía vacío); stock por bodega ahora suma todas las filas (base + cámaras) y `by_chamber` solo incluye filas con cámara.
- **Validaciones adicionales y correcciones críticas**:
  - `/inventory/movements` devolvía500 (bloque `@forelse` duplicado) → corregido; botón "Aprobar" integrado en la columna "Aprobado" (9 columnas consistentes).
  - `InventoryService` consultaba `stocks.bin_id` (columna inexistente) → **todo finalize de venta fallaba con error SQL**; el stock ahora se granula por `warehouse+product+chamber` (bin queda como trazabilidad en movimientos/ítems).
  - Migración `2026_11_06_000001_fix_stocks_unique_for_chambers`: unique `(warehouse_id, product_id)` → unique `(warehouse_id, COALESCE(chamber_id,0), product_id)` para permitir filas por cámara.
  - `SaleValidationService` valida stock con la misma granularidad (cámara) que `issueForSale`.
  - `InventoryController`: valida que la cámara pertenezca a la bodega (ajuste/ingreso/traslado).
  - `Controller` base ahora usa `AuthorizesRequests`/`ValidatesRequests` (`$this->authorize()` fallaba con500 en aprobación de movimientos).
  - Rol **ventas** ahora tiene `sales.cancel` (el límite de $100.000 era inalcanzable); deduplicado `inventory.approve` en `Role::allPermissions()`; roles re-sincronizados en DB.
  - `validation_errors` ahora se persiste al fallar la validación (el bloque de `sales/show` nunca se mostraba).
  - POS: IDs de cámara/bin coercidos a `Number` en `addEntry` (el `<select>` entrega string y los `find` usaban `===`).
  - Inventario UI: columna "Cámara" en la tabla y select de cámara en los3 modales (ajuste/ingreso/traslado) con prefill desde la fila.
- **Seeders**: nuevo `ChamberBinSeeder` (3 cámaras,4 bins,30 unidades base→CAM-01 por producto con stock≥50); `CatalogSeeder` y `WarehouseSeeder` con `chamber_id => null` explícito.
- **Frontend rebuild**: el bundle `public/build` estaba desactualizado (sin `entryChamberId`/`filteredBins`/`stock_by_warehouse`) y el POS era no interactivo → `npm run build` y assets sincronizados.

- **Menú lateral de mantenedores**: `/warehouses`, `/chambers`, `/bins` y `/calibers` existían (rutas, CRUD y vistas) pero **no tenían enlace en el sidebar**, así que no se veían desde la UI. Agregados en "Catálogo e inventario" con gating por permiso (`inventory.manage` para Bodegas/Cámaras/BINS, `products.manage` para Calibres); test E2E en `smoke.spec.ts` que valida los4 enlaces y la navegación.

## Módulos terminados (según código implementado)
- Auth y roles/permisos (Spatie): login, logout, middleware por rol/permiso.
- Dashboard con métricas y conteos por estado de venta.
- POS y gestión de ventas (borrador, validación, anulación con autorización por monto).
- Clientes (CRUD, búsqueda).
- Productos y categorías (CRUD, precios).
- Inventario (stock por bodega **y cámara**, movimientos, ajustes, ingresos, traslados con bin/cámara, aprobación de movimientos).
- Facturación (documentos, folios, contabilidad, PDF nota de venta, integración SII simulada).
- Reportes (listado y exportación).
- Usuarios (CRUD, roles, activación).
- Configuración (tasas, datos de empresa).
- Auditoría (log de acciones).
- Mantenedores Fase1: Calibre, BINS, Bodegas, Cámaras, Merma.
- Pagos Fase2: Crédito, Cheque a Fecha.

## Estado actual
- App Dockerizada operativa en http://localhost:8080 (web) con PostgreSQL (5433).
- Pruebas E2E: **20/20 en verde** (`npm run test:e2e`): smoke7, fase4 7, nuevos flujos6.
- Migración de granularidad de stock por cámara aplicada; ChamberBinSeeder corre en DB demo.
- Documentación de contexto y progreso actualizada.

## ⚠ Nota operativa importante
- **Docker monta `C:\SITIOS\banapal`** (no `C:\SITIOS\packing`, el directorio de trabajo). Tras cada edición en `packing`, **sincronizar los archivos cambiados a `C:\SITIOS\banapal`** (incluido `public/build` tras `npm run build`) o la app no reflejará los cambios. No copiar `packing\.env` sobre `banapal\.env` (packing tiene `DB_CONNECTION=sqlite`; banapal/`docker-compose` usa `pgsql`).

## Siguiente paso exacto
- Confirmar con el usuario si además debe ocultarse la ruta `billing.accounting` (hoy accesible por URL con permiso `accounting.view`) o eliminarse del todo (ruta + controlador + permiso).
- Decidir granularidad de **bins a nivel stock**: hoy `stocks` no tiene `bin_id` (bin es trazabilidad en `stock_movements`/`sale_items`); si se quiere stock por bin, crear migración (columna + unique compuesto) y poblar desde ChamberBinSeeder/inventario.
- Evaluar otorgar `inventory.approve` al rol `inventario` (hoy solo `admin` aprueba movimientos).
- Deuda de estilo: `vendor/bin/pint app` reporta≈28 archivos preexistentes fuera de convención (los archivos tocados en esta fase ya están limpios); correr Pint global si se quiere la suite verde de calidad.
- Flujos pendientes: edición de borradores (`updateDraft` no tiene ruta/UI) y mostrar `validation_errors` también en el POS al volver.
- Copiar cambios pendientes packing→banapal siempre que se edite código.

## Dudas o bloqueos
- Ninguno por ahora.
