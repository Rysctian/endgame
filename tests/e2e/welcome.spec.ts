import { expect, test } from '@playwright/test';

test('Laravel serves and mounts the Inertia Vue page', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));

    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle('Welcome - ENDGAME');
    await expect(page.getByRole('heading', { name: 'ENDGAME' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Laravel documentation' })).toBeVisible();
    expect(errors).toEqual([]);
});
