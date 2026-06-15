import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Expenses list',          url: '/finance/expenses' },
    { name: 'Expense create',         url: '/finance/expenses/create' },
    { name: 'Finance settings',       url: '/finance/settings' },
    { name: 'Salary list',            url: '/finance/salary' },
    { name: 'Salary create',          url: '/finance/salary/create' },
    { name: 'Payroll draft',          url: '/finance/payroll/draft' },
    { name: 'Payroll summary',        url: '/finance/payroll/summary' },
    { name: 'Attendance daily',       url: '/finance/payroll/attendance/daily' },
    { name: 'Attendance monthly',     url: '/finance/payroll/attendance/monthly' },
    { name: 'Report: Day book',       url: '/finance/reports/day-book' },
    { name: 'Report: Cash book',      url: '/finance/reports/cash-book' },
    { name: 'Report: Bank book',      url: '/finance/reports/bank-book' },
    { name: 'Report: Ledger',         url: '/finance/reports/ledger' },
    { name: 'Report: Trial balance',  url: '/finance/reports/trial-balance', timeout: 60000 },
    { name: 'Report: Profit & Loss',  url: '/finance/reports/profit-loss' },
];

for (const p of pages) {
    test(`Finance: ${p.name} load hoti hai`, async ({ page }) => {
        if (p.timeout) test.slow(); // triple the default timeout for heavy pages
        await page.goto(p.url, { timeout: p.timeout || 30000 });
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}

test('Finance: Expense create form ke required fields hain', async ({ page }) => {
    await page.goto('/finance/expenses/create');
    await expect(page.locator('input[name="expense_date"]')).toBeVisible();
    await expect(page.locator('select[name="expense_account_id"]')).toBeVisible();
    await expect(page.locator('input[name="amount"]')).toBeVisible();
    await expect(page.locator('select[name="payment_mode"]')).toBeVisible();
    await expect(page.locator('textarea[name="description"], input[name="description"]')).toBeVisible();
});
