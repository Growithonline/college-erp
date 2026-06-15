import { test, expect } from '@playwright/test';

test.describe('Certificate Module', () => {
    test('certificate list page load hoti hai', async ({ page }) => {
        await page.goto('/certificate');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500');
    });

    test('certificate issue page open hoti hai', async ({ page }) => {
        await page.goto('/certificate/issue');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500');
        // issueForm hidden hota hai jab tak student search na ho — page load verify kafi hai
        await expect(page.locator('#issueForm')).toBeAttached();
    });

    test('certificate settings page load hoti hai', async ({ page }) => {
        await page.goto('/certificate/settings');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

    test('certificate types page load hoti hai', async ({ page }) => {
        await page.goto('/certificate/types');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });
});
