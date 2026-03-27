import { expect, test } from '@playwright/test';

test.describe('public browser smoke tests', () => {
    test('landing page renders successfully', async ({ page }) => {
        await page.goto('/');

        await expect(page).toHaveTitle(/NTU/i);
        await expect(page.locator('body')).toContainText('NTU');
    });

    test('login page exposes the main form controls', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('#loginForm')).toBeVisible();
        await expect(page.locator('#email')).toBeVisible();
        await expect(page.locator('#matKhau')).toBeVisible();
        await expect(page.locator('#btnSubmit')).toBeVisible();
    });
});
