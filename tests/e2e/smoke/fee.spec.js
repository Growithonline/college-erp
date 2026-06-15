import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Fee collection page',       url: '/fee/collect' },
    { name: 'Fee search student',        url: '/fee/search-student' },
    { name: 'Practical tokens list',     url: '/fee/practical-tokens' },
    { name: 'Practical token create',    url: '/fee/practical-tokens/create' },
    { name: 'Students wallet list',      url: '/students/wallet' },
];

for (const p of pages) {
    test(`Fee: ${p.name} load hoti hai`, async ({ page }) => {
        await page.goto(p.url);
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}

test('Fee: Collection page pe student search field visible hai', async ({ page }) => {
    await page.goto('/fee/collect');
    await expect(page.locator('body')).not.toContainText('Server Error');
    // Search input hona chahiye
    const searchInput = page.locator('input[type="text"], input[type="search"], input[name*="search"], input[name*="student"], input[placeholder*="search" i], input[placeholder*="student" i]').first();
    await expect(searchInput).toBeVisible();
});
