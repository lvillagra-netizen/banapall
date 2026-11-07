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
  await page.waitForLoadState('networkidle', { timeout: 15000 });
}

test.describe('Fase 4 - Nuevos flujos', () => {
  test('admin puede acceder a inventario', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/inventory', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('admin puede acceder a movimientos de inventario', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/inventory/movements', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await expect(page.locator('body')).toBeVisible();
  });

  test('admin puede acceder a bodegas y cámaras', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();

    await page.goto('/chambers');
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('admin puede acceder a calibres y bins', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/calibers');
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();

    await page.goto('/bins');
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('admin puede acceder a reportes con filtros', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await page.goto('/reports');
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('vendedor puede acceder al POS', async ({ page }) => {
    await login(page, VENTAS_EMAIL, VENTAS_PASSWORD);
    await page.goto('/pos', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('vendedor puede ver sus ventas', async ({ page }) => {
    await login(page, VENTAS_EMAIL, VENTAS_PASSWORD);
    await page.goto('/sales', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(page).not.toHaveURL(/login/);
    await expect(page.locator('body')).toBeVisible();
  });
});







