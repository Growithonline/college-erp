import { test as setup } from '@playwright/test';
import path from 'path';

const authFile = path.join(import.meta.dirname, '../.auth/session.json');

setup('institute admin login', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="institute_id"]', process.env.TEST_INSTITUTE_UID || '');
    await page.fill('input[name="email"]', process.env.TEST_EMAIL || '');
    await page.fill('input[name="password"]', process.env.TEST_PASSWORD || '');
    await page.click('.btn-primary');

    await page.waitForURL('**/otp-verify**');
    await page.fill('input[name="otp"]', '999999');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**');

    // Session cookies save karo — sab tests reuse karenge
    await page.context().storageState({ path: authFile });
});
