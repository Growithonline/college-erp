import { test, expect } from '@playwright/test';

test.describe('Expense Workflow', () => {

    test('expense create karo → list mein dikhta hai → reverse karke cleanup', async ({ page }) => {
        // Step 1: Create page kholo
        await page.goto('/finance/expenses/create');
        await expect(page.locator('input[name="expense_date"]')).toBeVisible();

        // Step 2: Check karo expense_account dropdown mein options hain
        const accountSelect = page.locator('select[name="expense_account_id"]');
        await expect(accountSelect).toBeVisible();
        const optionCount = await accountSelect.locator('option').count();
        expect(optionCount).toBeGreaterThan(1); // placeholder ke alawa options hone chahiye

        // Step 3: Form fill karo
        await page.fill('input[name="expense_date"]', new Date().toISOString().split('T')[0]);
        await accountSelect.selectOption({ index: 1 }); // pehla real option
        await page.fill('input[name="amount"]', '1');
        await page.selectOption('select[name="payment_mode"]', 'cash');
        await page.fill('textarea[name="description"]', '[PLAYWRIGHT_TEST] Auto-generated test expense - safe to delete');

        // Step 4: Save button specifically click karo (logout button avoid karo)
        await page.click('button:has-text("Save Expense")');

        // Step 5: List page pe aana chahiye with success message
        await expect(page).toHaveURL(/finance\/expenses/);
        await expect(page.locator('.alert-success').first()).toBeVisible();

        // Step 6: Test expense list mein dikhna chahiye
        await expect(page.locator('body')).toContainText('PLAYWRIGHT_TEST');

        // Step 7: Reverse karo (cleanup) — test expense ki reverse link dhundo
        const reverseLink = page.locator('a[href*="reverse"]').first();
        if (await reverseLink.count() > 0) {
            await reverseLink.click();
            await page.fill('input[name="reversal_reason"], textarea[name="reversal_reason"]', 'Playwright automated test cleanup');
            await page.click('button:has-text("Save"), button[type="submit"]:not(:has-text("Logout"))');
            await expect(page).toHaveURL(/finance\/expenses/);
        }
    });

    test('expense form mein required fields validate hote hain', async ({ page }) => {
        await page.goto('/finance/expenses/create');
        // Save button click karo (logout button nahi)
        await page.click('button:has-text("Save Expense")');
        // Validation error ya page same rehna chahiye
        await expect(page).toHaveURL(/finance\/expenses\/create/);
    });

});
