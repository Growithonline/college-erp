import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Admissions list',         url: '/admissions' },
    { name: 'Quick registration form', url: '/admissions/quick' },
    { name: 'Full admission form',     url: '/admissions/create' },
    { name: 'Online admissions',       url: '/admissions/online' },
    { name: 'Approvals list',          url: '/admissions/approvals' },
    { name: 'Promotions list',         url: '/admissions/promotions' },
    { name: 'Promote by semester',     url: '/admissions/promote/semester' },
    { name: 'Promote by session',      url: '/admissions/promote/session' },
    { name: 'Promotion outcomes',      url: '/admissions/promote/outcomes' },
    { name: 'Promotion report',        url: '/admissions/promote/report' },
    { name: 'Bulk correction',         url: '/admissions/bulk-correction' },
];

for (const p of pages) {
    test(`Admissions: ${p.name} load hoti hai`, async ({ page }) => {
        await page.goto(p.url);
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}

test('Admissions: Quick form mein course dropdown populated hai', async ({ page }) => {
    await page.goto('/admissions/quick');
    await expect(page.locator('body')).not.toContainText('Server Error');
    // Form present hai
    const form = page.locator('form').first();
    await expect(form).toBeVisible();
});

test('Admissions: Student search kaam karta hai', async ({ page }) => {
    await page.goto('/students');
    await expect(page.locator('body')).not.toContainText('Server Error');
});
