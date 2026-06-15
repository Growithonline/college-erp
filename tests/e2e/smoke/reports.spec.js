import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Admission report',              url: '/reports/admission' },
    { name: 'Admission blocked report',      url: '/reports/admission/blocked' },
    { name: 'Admission centre report',       url: '/reports/admission/centre' },
    { name: 'Admission channel partner',     url: '/reports/admission/channel-partner' },
    { name: 'Admission full form',           url: '/reports/admission/full-form' },
    { name: 'Admission staff report',        url: '/reports/admission/staff' },
    { name: 'Fee collection report',         url: '/reports/fee-collection' },
    { name: 'Fee collection centre',         url: '/reports/fee-collection/centre' },
    { name: 'Fee collection channel-partner',url: '/reports/fee-collection/channel-partner' },
    { name: 'Fee collection staff',          url: '/reports/fee-collection/staff' },
    { name: 'Daily collection',              url: '/reports/daily-collection' },
    { name: 'Fee due list',                  url: '/reports/fee-due-list' },
    { name: 'Fee ledger',                    url: '/reports/fee-ledger' },
    { name: 'Cancelled fee',                 url: '/reports/cancelled-fee' },
    { name: 'Custom student report',         url: '/reports/custom-student' },
    { name: 'Stream-wise report',            url: '/reports/streams' },
    { name: 'Semester-wise report',          url: '/reports/semester-wise' },
    { name: 'Notices',                       url: '/notices' },
    { name: 'Notice create',                 url: '/notices/create' },
    // Profile page uses Breeze layout (needs proper integration - TODO)
];

for (const p of pages) {
    test(`Reports/Other: ${p.name} load hoti hai`, async ({ page }) => {
        await page.goto(p.url);
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}
