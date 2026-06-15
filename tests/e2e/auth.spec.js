import { test, expect } from '@playwright/test';

// Auth tests fresh page use karte hain (no stored session)
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Login Page', () => {
    test('login page load hoti hai', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('input[name="institute_id"]')).toBeVisible();
    });

    test('galat institute ID pe error aata hai', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="institute_id"]', 'INST-INVALID');
        await page.fill('input[name="email"]', 'wrong@email.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('.btn-primary');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.alert-danger')).toBeVisible();
    });

    test('sahi credentials se OTP page aata hai', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="institute_id"]', process.env.TEST_INSTITUTE_UID || '');
        await page.fill('input[name="email"]', process.env.TEST_EMAIL || '');
        await page.fill('input[name="password"]', process.env.TEST_PASSWORD || '');
        await page.click('.btn-primary');
        // Navigation wait karo (server se redirect aata hai)
        await page.waitForURL('**/otp-verify**', { timeout: 15000 });
        await expect(page.locator('input[name="otp"]')).toBeVisible();
    });
});
