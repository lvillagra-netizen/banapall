# PROGRESS.md

## Última tarea completada
- **Observación POS: el campo "Cantidad" ahora selecciona todo su valor al enfocarse** (`pos/index.blade.php`, `@focus="$event.target.select()"`). Antes, al hacer clic y escribir, el dígito se **concatenaba** (ej. `45` + `7` → `457`); ahora **reemplaza** (`45` + `7` → `7`).
  - Verificado con sonda Playwright antes/después en Chromium: sin `select()` → `17`/`457`; con `select()` → `7`. (`.select()` sí funciona en `input[type=number]`, aunque `selectionStart` devuelve `null`.)
  - **Test E2E nuevo**: "POS selecciona toda la cantidad al enfocar el campo Cantidad" (fill `45` → Tab → click → escribir `7` → espera `7`; sin el fix daría `457`).
  - Cambio solo en Blade → **el bundle JS no cambió** (`app-D7lBKMIH.js`); solo hizo falta `php artisan view:clear`. Test en verde.
- **Mismo ajuste en la sección Pagos**: el input **Monto** (`payments[i][amount]`) ahora también hace `@focus="$event.target.select()"` → al enfocar queda seleccionado todo el monto y escribir reemplaza en lugar de concatenar. Test E2E "POS selecciona todo el monto al enfocar un campo de Pagos" (fill `45` → Tab → click → `7` → espera `7`).
  - Validado con ambos tests: **E2E 24/24 en verde**.
- **Corrección: los pagos hechos en un Borrador no se guardaban ni se mostraban en Ventas y notas** (ej. Borrador #39):
  1. **Causa raíz**: `PosController::store/update` calculaban `$payments` pero solo los pasaban a `finalize()`; con `mode=draft` **se descartaban en silencio** (verificado en DB: `payments` sin filas para `sale_id=39`, `paid_total=0`). Por eso la tarjeta "Pagos" mostraba *"Aún no hay pagos registrados."*.
  2. **`PaymentService`** (refactor con `buildPayment()` compartido):
     - `recordDraft()` — persiste los pagos del borrador con estado **`pendiente`** y recalcula `paid_total`/`balance` del borrador.
     - `discardDraftPayments()` — borra los `pendiente` (el form del POS es la fuente de verdad al validar).
     - `confirmPending()` — al validar: `pendiente` → `confirmado` (+`paid_at`, `received_by`, asiento contable) y recalcula `paid_total`/`balance`/`status` (Validada/Completada).
     - `pendingTotal()` / `totalFor()`; `confirmedTotal()` ahora delega en `totalFor()`.
  3. **`SaleService::finalize(..., bool $paymentsAuthoritative = false)`**: si es autoritativo (POS) descarta los `pendiente` y registra los del form; si no (`SaleController::validateSale`, sin form) confirma los `pendiente` existentes. `SaleService::saveDraftPayments()` expone el guardado.
  4. **`PosController`**: `store`/`update` llaman `saveDraftPayments()` en `mode=draft` y pasan `true` en `mode=finalize`; `editingSale` ahora incluye `payments` (solo `pendiente`); `edit()` hace `load(['items','customer','payments'])`.
  5. **`app.js`**: `init()` precarga `editingSale.payments` (y re-agrega al listado un método filtrado —yape/plin/otro— para que el `<select>` lo muestre en vez de enviar `""`).
  6. **`sales/show`**: subtítulo dinámico (*"Pagos registrados en el borrador · pendientes de confirmar al validar"* vs *"Pagos confirmados y anulados"*); fecha usa `paid_at ?? created_at`. El badge `default => 'badge-warning'` ya renderizaba "Pendiente". Además `Sale::paymentStatus()` (usa `paid_total`) ahora muestra "Parcial"/"Pagado" también en borradores.
  - **Test E2E nuevo**: crea borrador con abono de 10.000 → detalle muestra *Pendiente* + Efectivo → Editar recarga method/amount → Validar y emitir → badge *Confirmado*.
  - Validado: `npm run build`, `view:clear`, Pint limpio, **E2E 22/22 en verde**.
  - ⚠ **Dato perdido**: los pagos que el usuario cargó en el **Borrador #39** nunca se persistieron (bug ya corregido) y no se pueden recuperar; hay que recargarlos vía "Editar borrador".
- **Corrección: "Editar" de un borrador mostraba el formulario vacío** (Ventas y notas → Editar):
  1. **Causa raíz**: `PosController::edit()` sí pasaba `$editingSale` a la vista y el JS ya tenía el bloque de precarga (`if (config.editingSale)`), pero `pos/index.blade.php` **no lo inyectaba en `window.__POS_CONFIG__`** → `config.editingSale` era `undefined`. Agregado `editingSale: @json($editingSale)`.
  2. **Cliente visible**: en `init()` se precarga `customerQuery` con el nombre del cliente del borrador (el buscador mostraba vacío aunque `customerId` estuviera seteado).
  3. **`stock` de cada ítem**: los items cargados no traían `stock` y `increment()` lo usa → se calcula desde `product.stock_by_warehouse` (bodega del borrador, o `by_chamber` si el ítem tiene cámara).
  4. **Bug colateral corregido**: los inputs ocultos usaban `:value="item.chamber_id || null"`; Alpine asigna `el.value = null` → se enviaba el **string `"null"`** y fallaba `exists:chambers,id` en cualquier venta sin cámara/bin. Cambiado a `?? ''` (el middleware global `ConvertEmptyStringsToNull` lo convierte en `null`).
  5. Quitado un `>` suelto tras `@endif` en el form (`@method('PUT')`) que se renderizaba como texto visible.
  - **Test E2E nuevo** (`nuevos-flujos.spec.ts` "POS carga los datos de un borrador al editar y permite guardarlo"): crea borrador → Editar → verifica cliente + producto en carrito → cambia cantidad a 2 → guarda y confirma "Borrador de venta actualizado correctamente.".
  - Validado: `npm run build`, `view:clear`, **E2E 21/21 en verde**.
- **7 ajustes de UX/ventas en POS (reunión)**, todos aplicados y verificados con `npm run build`, `view:clear`, `cache:clear` y E2E **20/20 en verde**:
  1. **Modal de stock**: si cantidad > stock, mensaje "La cantidad supera el stock disponible." en modal (`showStockAlert` en `app.js` + markup en `pos/index.blade.php`); `addEntry`/`increment` ya no usan `notify` con ese texto (evita fallo de strict mode en `nuevos-flujos.spec.ts:75`).
  2. **Moneda e Impuesto deshabilitados** en "Cliente y Condiciones" (CLP / IVA 19%); se quitaron sus `@change`/`onCurrencyChange` del binding y `onCustomerChange` ya no pisa moneda/impuesto desde el cliente.
  3. **Bloque "Tipo de cambio" eliminado** del POS (la línea en `sales/show` Trazabilidad se mantine por ser otra vista).
  4. **Bodega de salida eliminada** como selector visible → `<input hidden name="warehouse_id">` (`PosController::store` lo requiere).
  5. **Pagos: moneda deshabilitada** (select `disabled` sin `name` + hidden `payments[i][currency_code]`).
  6. **Botón "− Pago"** en header de Pagos (`removeLastPayment`, deshabilitado si no hay pagos).
  7. **Línea neta sin IVA**: carrito usa `lineTaxable(item)`; PDF (`nota-venta` L259) y `sales/show` L97 usan `Money::subtractCurrency(currency, line_subtotal, discount_amount)`. Desglose Neto/Descuentos/Impuesto/Total queda solo en la tarjeta de Notas. `lineTotal` sigue alimentando "Total a cobrar" de la barra fija.
  - Además: ocultos métodos de pago `yape`/`plin`/`otro` en `init()`; `filteredChambers` filtra por `warehouseId` fijo (evita cámara de otra bodega con stock 0); `warehouse_name` salió del label de cámara.
- **3 fallos E2E preexistentes corregidos**: bins ordenados por `code` en `PosController` (index 1 = BIN-A1); test de validación ahora clica "Confirmar y emitir" tras el modal; `playwright.config.ts` timeout 60s→120s (smoke /chambers era flaky por tiempo acumulado).
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
- Pruebas E2E: **24/24 en verde** (`npm run test:e2e`): smoke7, fase4 7, nuevos flujos10.
- Migración de granularidad de stock por cámara aplicada; ChamberBinSeeder corre en DB demo.
- Documentación de contexto y progreso actualizada.

## ⚠ Nota operativa importante
- **Docker monta `C:\SITIOS\banapal`** (no `C:\SITIOS\packing`, el directorio de trabajo). Tras cada edición en `packing`, **sincronizar los archivos cambiados a `C:\SITIOS\banapal`** (incluido `public/build` tras `npm run build`) o la app no reflejará los cambios. No copiar `packing\.env` sobre `banapal\.env` (packing tiene `DB_CONNECTION=sqlite`; banapal/`docker-compose` usa `pgsql`).

## Siguiente paso exacto
- **Subir a GitHub** los cambios aún sin commitear (selección automática de texto al enfocar en **Cantidad** y en **Monto** de Pagos: `resources/views/pos/index.blade.php`, `tests/e2e/nuevos-flujos.spec.ts`, `PROGRESS.md`): `git add -A` → `git commit` → `git push origin main`.
- **Recargar los pagos del Borrador #39** (no persistidos por el bug ya corregido) vía "Editar borrador" → sección Pagos → Borrador.
- **Revisión visual del POS** en http://localhost:8080/pos con el usuario para confirmar los 7 ajustes (modal de stock, selects deshabilitados, sin Bodega/Tipo de cambio, "− Pago", líneas netas).
- Pregunta abierta: ¿el desglose "Neto/Descuentos/Impuesto/Total" debe quedar también oculto en otras vistas (solo Notas) o solo se ajustó el POS y los PDFs?
- Confirmar con el usuario si además debe ocultarse la ruta `billing.accounting` (hoy accesible por URL con permiso `accounting.view`) o eliminarse del todo (ruta + controlador + permiso).
- Decidir granularidad de **bins a nivel stock**: hoy `stocks` no tiene `bin_id` (bin es trazabilidad en `stock_movements`/`sale_items`); si se quiere stock por bin, crear migración (columna + unique compuesto) y poblar desde ChamberBinSeeder/inventario.
- Evaluar otorgar `inventory.approve` al rol `inventario` (hoy solo `admin` aprueba movimientos).
- Deuda de estilo: `vendor/bin/pint app` reporta≈28 archivos preexistentes fuera de convención (los archivos tocados en esta fase ya están limpios); correr Pint global si se quiere la suite verde de calidad.
- Flujos pendientes: mostrar `validation_errors` también en el POS al volver (hoy solo en `sales/show`).
- Copiar cambios pendientes packing→banapal siempre que se edite código.

## Dudas o bloqueos
- Ninguno por ahora.
