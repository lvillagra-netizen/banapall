import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@banapal.cl';
const ADMIN_PASSWORD = process.env.BANAPAL_ADMIN_PASSWORD || 'Banapal2026!';
const VENTAS_EMAIL = 'ventas@banapal.cl';
const VENTAS_PASSWORD = process.env.BANAPAL_ADMIN_PASSWORD || 'Banapal2026!';

async function login(page, email, password) {
  await page.goto('/login');
  await page.getByLabel(/correo/i).fill(email);
  await page.getByLabel(/contraseña/i).fill(password);
  await page.getByRole('button', { name: /ingresar/i }).click();
  await page.waitForLoadState('networkidle', { timeout: 20000 });
}

async function addFirstProduct(page, query: string) {
  const search = page.getByPlaceholder('Buscar por nombre o SKU…');
  await search.click();
  await search.fill(query);
  await page.waitForTimeout(300);
  await search.press('Enter');
}

async function selectFirstCustomer(page) {
  const customerSearch = page.getByPlaceholder('Buscar por nombre o RUT…');
  await customerSearch.click();
  await customerSearch.fill('a');
  await page.waitForTimeout(300);
  await page.locator('button.block.w-full:visible').first().click();
}

test.describe('Nuevos flujos - POS, inventario y anulaciones', () => {
  test.beforeEach(({ page }) => {
    page.on('dialog', (dialog) => dialog.accept());
  });

  test('POS permite elegir cámara y bin, y guarda borrador', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/pos', { waitUntil: 'domcontentloaded', timeout: 60000 });

    await addFirstProduct(page, 'HASS-10');

    await expect(page.locator('span:has-text("Stock:")')).toBeVisible();

    const chamberSelect = page.locator('label:has-text("Cámara (opcional)") + select');
    await expect(chamberSelect).toBeVisible();
    await chamberSelect.selectOption({ index: 1 });
    await expect(page.getByText('(cámara)')).toBeVisible();

    const binSelect = page.locator('label:has-text("Bin (opcional)") + select');
    await expect(binSelect).toBeVisible();
    await binSelect.selectOption({ index: 1 });

    await page.locator('[x-ref="quantityInput"]').fill('1');
    await page.getByRole('button', { name: 'Agregar', exact: true }).click();

    await expect(page.getByText('Cámara: Cámara 1')).toBeVisible();
    await expect(page.getByText('Bin: BIN-A1')).toBeVisible();

    await selectFirstCustomer(page);

    await page.getByRole('button', { name: 'Borrador', exact: true }).click();
    await expect(page).toHaveURL(/\/sales\/\d+/, { timeout: 30000 });
    await expect(page.getByText(/Borrador de venta guardado/)).toBeVisible();
  });

  test('POS valida que la cantidad no supere el stock disponible', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/pos', { waitUntil: 'domcontentloaded', timeout: 60000 });

    await addFirstProduct(page, 'HASS-10');
    await page.locator('[x-ref="quantityInput"]').fill('99999999');
    await page.getByRole('button', { name: 'Agregar', exact: true }).click();

    await expect(page.getByText('La cantidad supera el stock disponible.')).toBeVisible();
  });

  test('inventario muestra stock por cámara con columna dedicada', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/inventory', { waitUntil: 'domcontentloaded', timeout: 60000 });

    await expect(page.getByRole('columnheader', { name: 'Cámara' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Cámara 1' }).first()).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Sin cámara' }).first()).toBeVisible();
  });

  test('admin aprueba un movimiento de inventario pendiente', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/inventory/movements', { waitUntil: 'domcontentloaded', timeout: 60000 });

    await expect(page.getByRole('heading', { name: 'Historial' })).toBeVisible();

    await page.getByRole('link', { name: /Pendientes de aprobación/ }).click();
    await expect(page).toHaveURL(/pending=1/);
    await expect(page.getByRole('heading', { name: 'Historial' })).toBeVisible();

    const approveButton = page.getByRole('button', { name: 'Aprobar', exact: true }).first();
    await expect(approveButton).toBeVisible();
    await approveButton.click();

    await expect(page.getByText(/Movimiento aprobado correctamente/)).toBeVisible();
  });

  test('admin valida venta desde el POS y luego la anula', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/pos', { waitUntil: 'domcontentloaded', timeout: 60000 });

    await addFirstProduct(page, 'HASS-10');
    await page.locator('[x-ref="quantityInput"]').fill('1');
    await page.getByRole('button', { name: 'Agregar', exact: true }).click();

    const customerSearch = page.getByPlaceholder('Buscar por nombre o RUT…');
    await customerSearch.click();
    await customerSearch.fill('a');
    await page.waitForTimeout(300);
    await page.locator('button.block.w-full:visible').first().click();

    await page.getByRole('button', { name: 'Validar y emitir' }).click();
    await expect(page).toHaveURL(/\/sales\/\d+/, { timeout: 30000 });
    await expect(page.getByText(/validada correctamente/)).toBeVisible();

    await page.getByRole('button', { name: 'Anular', exact: true }).click();
    await page.locator('#cancel-sale textarea').fill('Anulación generada por prueba E2E');
    await page.getByRole('button', { name: 'Confirmar anulación' }).click();

    await expect(page.getByText(/Venta anulada/).first()).toBeVisible();
    await expect(page.getByText(/La venta anulada el|Venta anulada el/).first()).toBeVisible();
  });

  test('vendedor no puede anular ventas sobre el límite de $100.000 CLP', async ({ page }) => {
    await login(page, VENTAS_EMAIL, VENTAS_PASSWORD);
    await page.goto('/sales/7', { waitUntil: 'domcontentloaded', timeout: 60000 });

    const cancelButton = page.getByRole('button', { name: 'Anular', exact: true });
    await expect(cancelButton).toBeVisible();

    await cancelButton.click();
    await page.locator('#cancel-sale textarea').fill('Intento de anulación fuera de límite E2E');
    await page.getByRole('button', { name: 'Confirmar anulación' }).click();

    await expect(page.getByText(/No puedes anular ventas mayores a \$100\.000 CLP/)).toBeVisible();
    await expect(page.getByText('Validada').first()).toBeVisible();
  });
});
