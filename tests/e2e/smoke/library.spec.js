import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Library dashboard',    url: '/library' },
    { name: 'Books list',           url: '/library/books' },
    { name: 'Book create',          url: '/library/books/create' },
    { name: 'Authors',              url: '/library/authors' },
    { name: 'Publishers',           url: '/library/publishers' },
    { name: 'Categories',           url: '/library/categories' },
    { name: 'Subjects',             url: '/library/subjects' },
    { name: 'Racks',                url: '/library/racks' },
    { name: 'Vendors',              url: '/library/vendors' },
    { name: 'Members',              url: '/library/members' },
    { name: 'Circulation',          url: '/library/circulation' },
    { name: 'Reservations',         url: '/library/reservations' },
    { name: 'Rules',                url: '/library/rules' },
    { name: 'Reports',              url: '/library/reports' },
    { name: 'No-due list',          url: '/library/no-due' },
    { name: 'Fine collection',      url: '/library/fines' },
];

for (const p of pages) {
    test(`Library: ${p.name} load hoti hai`, async ({ page }) => {
        await page.goto(p.url);
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}
