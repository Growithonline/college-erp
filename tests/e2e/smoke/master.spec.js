import { test, expect } from '@playwright/test';

const pages = [
    { name: 'Courses list',          url: '/master/courses' },
    { name: 'Course types',          url: '/master/course-types' },
    { name: 'Sessions list',         url: '/master/sessions' },
    { name: 'Session create',        url: '/master/sessions/create' },
    { name: 'Fee types list',        url: '/master/fee-types' },
    { name: 'Fee type create',       url: '/master/fee-types/create' },
    { name: 'Fee assignments',       url: '/master/fee-assignments' },
    { name: 'Fee structure courses', url: '/master/fee-structure/course-fees' },
    { name: 'Fee structure subjects',url: '/master/fee-structure/subject-fees' },
    { name: 'Staff members list',    url: '/master/staff-members' },
    { name: 'Staff member create',   url: '/master/staff-members/create' },
    { name: 'Staff roles list',      url: '/master/staff-roles' },
    { name: 'Staff role create',     url: '/master/staff-roles/create' },
    { name: 'Subjects list',         url: '/master/subjects' },
    { name: 'Subject create',        url: '/master/subjects/create' },
    { name: 'Bank accounts list',    url: '/master/bank-accounts' },
    { name: 'Bank account create',   url: '/master/bank-accounts/create' },
    { name: 'Student types',         url: '/master/student-types' },
    { name: 'Document types',        url: '/master/document-types' },
    { name: 'Document categories',   url: '/master/document-categories' },
    { name: 'Centers list',          url: '/master/centers' },
    { name: 'Channel partners',      url: '/master/channel-partners' },
];

for (const p of pages) {
    test(`Master: ${p.name} load hoti hai`, async ({ page }) => {
        await page.goto(p.url);
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
        await expect(page.locator('body')).not.toContainText('Whoops');
        await expect(page).not.toHaveURL(/login/);
    });
}
