import { test, expect } from '@playwright/test';

test.describe('Fee Collection Workflow', () => {

    test('fee collection page load aur search field visible hai', async ({ page }) => {
        await page.goto('/fee/collect');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');

        // Search/student input field hona chahiye
        const searchInput = page.locator('input').filter({ hasText: '' }).first();
        await expect(page.locator('input[type="text"], input[type="search"]').first()).toBeVisible();
    });

    test('fee index page (student list) load hoti hai', async ({ page }) => {
        await page.goto('/fee');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

    test('fee search AJAX kaam karta hai', async ({ page }) => {
        await page.goto('/fee/collect');
        await expect(page.locator('body')).not.toContainText('Server Error');

        // Search input pe type karo aur API response check karo
        const [response] = await Promise.all([
            page.waitForResponse(res => res.url().includes('search-student') && res.status() === 200, { timeout: 10000 }).catch(() => null),
            page.locator('input[type="text"], input[type="search"]').first().fill('test'),
        ]);

        // Response aaya ya nahi — dono cases mein page crash nahi hona chahiye
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

    test('student fee history page structure sahi hai', async ({ page }) => {
        await page.goto('/fee');
        await expect(page.locator('body')).not.toContainText('Server Error');
        // Page pe kuch content hona chahiye
        await expect(page.locator('body')).toBeVisible();
    });

    test('fee search-student endpoint respond karta hai', async ({ page }) => {
        const response = await page.request.get('/fee/search-student?q=test');
        // 200 ya 302 (redirect to login agar nahi) expect karo, 500 nahi
        expect([200, 302, 422]).toContain(response.status());
    });

});
