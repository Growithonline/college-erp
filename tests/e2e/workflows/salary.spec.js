import { test, expect } from '@playwright/test';

test.describe('Salary & Payroll Workflow', () => {

    test('payroll draft page load hoti hai', async ({ page }) => {
        await page.goto('/finance/payroll/draft');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');

        // Filter form hona chahiye
        await expect(page.locator('select[name="year"]')).toBeVisible();
        await expect(page.locator('select[name="month"]')).toBeVisible();
        await expect(page.locator('button#generateDraftBtn, button:has-text("Generate Draft")')).toBeVisible();
    });

    test('payroll draft generate API respond karta hai', async ({ page }) => {
        await page.goto('/finance/payroll/draft');
        await expect(page.locator('body')).not.toContainText('Server Error');

        const currentDate = new Date();
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;

        // Page ke andar se fetch karo — iss se CSRF token aur cookies automatically sahi honge
        const result = await page.evaluate(async ({ year, month }) => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/finance/payroll/generate-draft', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ year: String(year), month: String(month) }),
            });
            const body = await response.json().catch(() => ({}));
            return { status: response.status, body };
        }, { year, month });

        // 200 (success) ya 422 (no staff/data) expect karo — 500 nahi
        expect([200, 422]).toContain(result.status);
        expect(result.body).toHaveProperty('success');
    });

    test('payroll summary page load hoti hai', async ({ page }) => {
        await page.goto('/finance/payroll/summary');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
    });

    test('salary list page load hoti hai', async ({ page }) => {
        await page.goto('/finance/salary');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
    });

    test('salary create page load hoti hai', async ({ page }) => {
        await page.goto('/finance/salary/create');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
    });

    test('attendance daily page load hoti hai', async ({ page }) => {
        await page.goto('/finance/payroll/attendance/daily');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

    test('attendance monthly page load hoti hai', async ({ page }) => {
        await page.goto('/finance/payroll/attendance/monthly');
        await expect(page.locator('body')).not.toContainText('Server Error');
    });

});
