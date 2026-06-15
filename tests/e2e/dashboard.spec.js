import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
    test('dashboard load hota hai', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/dashboard/);
        await expect(page.locator('body')).toBeVisible();
    });

    test('sidebar navigation visible hai', async ({ page }) => {
        await page.goto('/dashboard');
        const nav = page.locator('nav, aside, .sidebar, #sidebar').first();
        await expect(nav).toBeVisible();
    });
});
