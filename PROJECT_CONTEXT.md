# PROJECT_CONTEXT.md

## Nombre del proyecto
**BANAPAL** — sistema interno de packing/comercialización (POS, inventario, facturación, reportes).

## Stack
- **Backend:** Laravel 13 (PHP 8.3+), Eloquent, Spatie Permission.
- **Frontend:** Blade + Tailwind CSS 4 + Alpine.js + Chart.js, compilado con Vite.
- **Base de datos:** PostgreSQL 17 (Docker). Redis/Memcached opcionales.
- **Infraestructura:** Docker Compose (app PHP-FPM, nginx, postgres, node para build, adminer opcional). **El contenedor monta `C:\SITIOS\banapal` como `/var/www/html`**; el repo de trabajo es `C:\SITIOS\packing`: tras editar, sincronizar archivos cambiados (y `public/build` tras `npm run build`) hacia `banapal`. `docker-compose` fuerza `DB_CONNECTION=pgsql`; no sobrescribir `banapal\.env` con `packing\.env` (este declara `sqlite`).
- **PDF:** barryvdh/laravel-dompdf.
- **Testing:** PHPUnit 12 (backend) + Playwright (E2E, Chromium).
- **Calidad:** Laravel Pint.

## Arquitectura
Arquitectura MVC clásica de Laravel con capa de servicios:
- `Controllers` delegan en `Services` (SaleService, PaymentService, InventoryService, DocumentService, etc.).
- `Enums` tipan estados y opciones (SaleStatus, DocumentStatus, Role, etc.).
- `Contracts` para conectores externos (SII, contabilidad) con implementaciones simuladas.
- Vistas Blade por módulo en `resources/views`.
- Roles/permisos con Spatie Permission (`admin`, `ventas`, `inventario`, `contabilidad`).

## Carpetas clave
- `app/Http/Controllers` — controladores por módulo.
- `app/Services` — lógica de negocio (ventas, pagos, inventario, documentos, folios).
- `app/Models` — Eloquent (Sale, Customer, Product, Document, Payment, Stock, etc.).
- `app/Enums` — estados y catálogos.
- `app/Contracts` + `app/Services` — conectores SII/contabilidad.
- `database/migrations`, `database/seeders`, `database/factories`.
- `resources/views` — vistas Blade por módulo.
- `resources/js`, `resources/css` — frontend (Alpine, Tailwind).
- `routes/web.php` — rutas web y middleware de auth/permisos.
- `tests/Feature`, `tests/Unit`, `tests/e2e` — pruebas.
- `docker/` — configuración nginx/postgres/entrypoint.
- `docs/` — documentación y diagramas.

## Módulos principales
Auth/roles, Dashboard, POS, Ventas, Clientes, Productos/Categorías, Inventario, Facturación (documentos/folios/contabilidad), Reportes, Usuarios, Configuración, Auditoría.
