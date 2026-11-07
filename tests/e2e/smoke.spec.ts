import { test, expect } from '@playwright/test';

const ADMIN_EMAIL = 'admin@banapal.cl';
const ADMIN_PASSWORD = process.env.BANAPAL_ADMIN_PASSWORD || 'Banapal2026!';

test.describe('BANAPAL smoke tests', () => {
  test('landing page loads', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('body')).toBeVisible();
    await expect(page).toHaveTitle(/.+/);
  });

  test('login page renders demo accounts', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByLabel(/correo/i)).toBeVisible();
    await expect(page.getByLabel(/contraseña/i)).toBeVisible();
    await expect(page.getByText('admin@banapal.cl')).toBeVisible();
    await expect(page.getByText('Banapal2026!')).toBeVisible();
  });

  test('admin can log in and reach dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel(/correo/i).fill(ADMIN_EMAIL);
    await page.getByLabel(/contraseña/i).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /ingresar/i }).click();
    await page.waitForURL(/dashboard|admin/, { timeout: 15000 });
    await expect(page).toHaveURL(/dashboard|admin/);
  });

  test('invalid credentials show an error', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel(/correo/i).fill('noexiste@banapal.cl');
    await page.getByLabel(/contraseña/i).fill('wrong-password');
    await page.getByRole('button', { name: /ingresar/i }).click();
    await expect(page).toHaveURL(/login/);
  });

  test('admin can navigate key sections', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel(/correo/i).fill(ADMIN_EMAIL);
    await page.getByLabel(/contraseña/i).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /ingresar/i }).click();
    await page.waitForURL(/dashboard|admin/, { timeout: 15000 });

    const sections = ['/pos', '/products', '/customers', '/inventory', '/sales', '/warehouses', '/chambers', '/bins', '/calibers'];
    for (const path of sections) {
      console.log('VISITING', path);
      const response = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 20000 });
      console.log('STATUS', path, response?.status());
      await expect(page).not.toHaveURL(/login/);
      await expect(page.locator('body')).toBeVisible();
  }
  });

  test('sidebar shows warehouse, chamber, bin and caliber maintainers', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel(/correo/i).fill(ADMIN_EMAIL);
    await page.getByLabel(/contraseña/i).fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /ingresar/i }).click();
    await page.waitForURL(/dashboard|admin/, { timeout: 15000 });

    const sidebar = page.locator('aside nav');
    for (const label of ['Bodegas', 'Cámaras', 'BINS', 'Calibres']) {
      await expect(sidebar.getByRole('link', { name: label })).toBeVisible();
    }
    await sidebar.getByRole('link', { name: 'Bodegas' }).click();
    await expect(page).toHaveURL(/\/warehouses/);
    await expect(page.locator('body')).toContainText(/bodega/i);
  });

  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForURL(/login/, { timeout: 10000 });
    await expect(page).toHaveURL(/login/);
  });

});

