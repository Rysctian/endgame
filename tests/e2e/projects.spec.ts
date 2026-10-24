import { expect, test, type Page } from '@playwright/test';

async function login(page: Page, name: string) {
    await page.goto('/login');
    await page
        .getByLabel('Email', { exact: true })
        .fill(name + '@endgame.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('browser-password-123');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/workspaces$/);
}

test('project creation, scoped membership, archive, restore and access revocation', async ({
    page,
    browser,
    baseURL,
}) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await login(page, 'projectmanager');
    await page.getByRole('link', { name: 'Project team', exact: true }).click();
    await page.getByRole('link', { name: 'Projects', exact: true }).click();
    await page
        .getByRole('button', { name: 'Create project', exact: true })
        .click();
    await page
        .getByLabel('Project name', { exact: true })
        .fill('Launch Portal');
    await page.getByLabel('Project key', { exact: true }).fill('PORTAL');
    await page
        .getByLabel('Description', { exact: true })
        .fill('First delivery.');
    await page
        .getByRole('button', { name: 'Save project', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Launch Portal', exact: true }),
    ).toBeVisible();
    const projectUrl = page.url();
    await page
        .getByRole('button', { name: 'Edit project', exact: true })
        .click();
    await expect(
        page.getByLabel('Project key', { exact: true }),
    ).toHaveAttribute('readonly', '');
    await page
        .getByLabel('Project name', { exact: true })
        .fill('Launch Portal v2');
    await page
        .getByRole('button', { name: 'Save project', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Launch Portal v2', exact: true }),
    ).toBeVisible();
    await page.getByRole('button', { name: 'Add member', exact: true }).click();
    await page
        .getByRole('combobox', { name: 'Workspace member', exact: true })
        .click();
    await page.getByRole('option', { name: /Projectmember/ }).click();
    await page
        .getByRole('button', { name: 'Save member', exact: true })
        .click();
    await expect(
        page.getByText('projectmember@endgame.test', { exact: true }),
    ).toBeVisible();

    const context = await browser.newContext({ baseURL });
    const member = await context.newPage();
    member.on('pageerror', (error) => errors.push(error.message));
    await login(member, 'projectmember');
    await member.goto(projectUrl);
    await expect(
        member.getByRole('heading', { name: 'Launch Portal v2', exact: true }),
    ).toBeVisible();
    await expect(
        member.getByRole('button', { name: 'Edit project', exact: true }),
    ).toHaveCount(0);
    await expect(
        member.getByRole('button', { name: 'Add member', exact: true }),
    ).toHaveCount(0);

    await page
        .getByRole('button', { name: 'Archive project', exact: true })
        .click();
    await page
        .getByRole('button', { name: 'Confirm archive', exact: true })
        .click();
    await expect(
        page.getByText('Archived projects are read-only.', { exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'Edit project', exact: true }),
    ).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: 'Add member', exact: true }),
    ).toHaveCount(0);
    await member.reload();
    await expect(
        member.getByText('Archived projects are read-only.', { exact: true }),
    ).toBeVisible();
    await expect(
        member.getByRole('button', { name: 'Restore project', exact: true }),
    ).toHaveCount(0);
    await page
        .getByRole('button', { name: 'Restore project', exact: true })
        .click();
    await page
        .getByRole('button', { name: 'Confirm restore', exact: true })
        .click();
    await expect(page.getByRole('status')).toContainText('Project restored');
    await page
        .getByRole('row')
        .filter({ hasText: 'projectmember@endgame.test' })
        .getByRole('button', { name: 'Remove member', exact: true })
        .click();
    await page
        .getByRole('button', { name: 'Confirm removal', exact: true })
        .click();
    const response = await member.reload();
    expect(response?.status()).toBe(403);
    await context.close();
    await page.getByRole('link', { name: 'Projects', exact: true }).click();
    await page.getByLabel('Search projects', { exact: true }).fill('No match');
    await page
        .getByRole('button', { name: 'Apply filters', exact: true })
        .click();
    await expect(
        page.getByText('No projects found.', { exact: true }),
    ).toBeVisible();
    expect(errors).toEqual([]);
});
