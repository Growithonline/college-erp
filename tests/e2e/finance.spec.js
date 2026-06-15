import { test, expect } from '@playwright/test';

test.describe('Finance Module', () => {
    test('expenses page load hoti hai', async ({ page }) => {
        await page.goto('/finance/expenses');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('finance settings page load hoti hai', async ({ page }) => {
        await page.goto('/finance/settings');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

    test('payroll page load hota hai', async ({ page }) => {
        await page.goto('/finance/payroll');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });
});
