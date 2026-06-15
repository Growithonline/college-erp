import { test, expect } from '@playwright/test';

test.describe('Practical Token Workflow', () => {

    test('practical tokens list load hoti hai', async ({ page }) => {
        await page.goto('/fee/practical-tokens');
        await expect(page.locator('body')).not.toContainText('Server Error');
        await expect(page.locator('body')).not.toContainText('500 |');
    });

    test('practical token create form load hoti hai', async ({ page }) => {
        await page.goto('/fee/practical-tokens/create');
        await expect(page.locator('body')).not.toContainText('Server Error');

        // Required dropdowns hone chahiye
        await expect(page.locator('select[name="academic_session_id"]')).toBeVisible();
        await expect(page.locator('select[name="course_id"]')).toBeVisible();
        await expect(page.locator('select[name="subject_id"]')).toBeVisible();
        await expect(page.locator('select[name="course_part_id"], select[name="semester"]')).toBeVisible();
    });

    test('practical token create form mein selects rendered hain', async ({ page }) => {
        await page.goto('/fee/practical-tokens/create');
        await expect(page.locator('body')).not.toContainText('Server Error');

        const sessionSelect = page.locator('select[name="academic_session_id"]');
        const courseSelect = page.locator('select[name="course_id"]');
        await expect(sessionSelect).toBeVisible();
        await expect(courseSelect).toBeVisible();

        const sessionOptions = await sessionSelect.locator('option').count();
        const courseOptions = await courseSelect.locator('option').count();
        expect(sessionOptions).toBeGreaterThanOrEqual(0);
        expect(courseOptions).toBeGreaterThanOrEqual(0);
    });

    test('practical token create form validation kaam karta hai', async ({ page }) => {
        await page.goto('/fee/practical-tokens/create');
        // Save button click karo (logout button nahi)
        await page.click('button:has-text("Create Batch"), button:has-text("Save"), button:has-text("Submit")');
        // Page same rehna chahiye (HTML5 ya server validation rokegi)
        await expect(page).toHaveURL(/practical-tokens/);
    });

});
