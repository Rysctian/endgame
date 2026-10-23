import { expect, test } from '@playwright/test';
test('application entry directs guests to sign in', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto('/');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page).toHaveTitle('Sign in - ENDGAME');
    await expect(
        page.getByRole('heading', { name: 'Sign in', exact: true }),
    ).toBeVisible();
    expect(errors).toEqual([]);
});
