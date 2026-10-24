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
test('registration, verification notice and logout work', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto('/register');
    await page.getByLabel('Name', { exact: true }).fill('New person');
    await page.getByLabel('Email', { exact: true }).fill('new@endgame.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('registration-password');
    await page
        .getByLabel('Confirm password', { exact: true })
        .fill('registration-password');
    await page
        .getByRole('button', { name: 'Create account', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Verify your email' }),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'Resend verification email' })
        .click();
    await expect(page.getByRole('status')).toContainText(
        'Verification link sent',
    );
    await page.getByRole('button', { name: 'Sign out' }).click();
    await expect(page).toHaveURL(/\/login$/);
    expect(errors).toEqual([]);
});
test('recipient accepts invitation and manager manages membership and roles', async ({
    page,
}) => {
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    const invitation = '/invitations/1/' + 'a'.repeat(64);
    await page.goto(invitation);
    await page
        .getByLabel('Email', { exact: true })
        .fill('recipient@endgame.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('browser-password-123');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(
        page.getByRole('heading', { name: 'Join Browser team' }),
    ).toBeVisible();
    await page.getByRole('button', { name: 'Accept invitation' }).click();
    await expect(
        page.getByRole('heading', { name: 'Browser team', exact: true }),
    ).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'Invite member' }),
    ).toHaveCount(0);
    await page.getByRole('button', { name: 'Sign out' }).click();
    await login(page, 'manager');
    await page.getByRole('link', { name: 'Browser team', exact: true }).click();
    await expect(page.getByText('recipient@endgame.test')).toBeVisible();
    await page.getByRole('button', { name: 'Create role' }).click();
    await page.getByLabel('Role name').fill('Contributor');
    await page.getByLabel('Role code').fill('contributor');
    await page.getByRole('button', { name: 'Save role' }).click();
    await expect(page.getByRole('status')).toContainText('Role created');
    const recipient = page
        .getByRole('row')
        .filter({ hasText: 'recipient@endgame.test' });
    await recipient.getByRole('button', { name: 'Change role' }).click();
    await page.getByRole('combobox', { name: 'Workspace role' }).click();
    await page
        .getByRole('option', { name: 'Contributor', exact: true })
        .click();
    await page.getByRole('button', { name: 'Save member' }).click();
    await expect(recipient).toContainText('Contributor');
    await page
        .getByRole('button', { name: 'Invite member', exact: true })
        .click();
    await page.getByLabel('Invitation email').fill('next@endgame.test');
    await page.getByRole('combobox', { name: 'Workspace role' }).click();
    await page.getByRole('option', { name: 'Member', exact: true }).click();
    await page.getByRole('button', { name: 'Send invitation' }).click();
    await expect(page.getByText('next@endgame.test')).toBeVisible();
    await recipient.getByRole('button', { name: 'Remove member' }).click();
    await page.getByRole('button', { name: 'Confirm removal' }).click();
    await expect(page.getByText('recipient@endgame.test')).toHaveCount(0);
    expect(errors).toEqual([]);
});
test('admin creates workspace and suspends an account', async ({ page }) => {
    await login(page, 'admin');
    await page
        .getByRole('button', { name: 'Create workspace', exact: true })
        .click();
    await page.getByLabel('Workspace name').fill('Admin workspace');
    await page.getByLabel('Slug', { exact: true }).fill('admin-workspace');
    await page.getByRole('button', { name: 'Save workspace' }).click();
    await expect(
        page.getByRole('heading', { name: 'Admin workspace', exact: true }),
    ).toBeVisible();
    await page.getByRole('link', { name: 'Accounts', exact: true }).click();
    const row = page
        .getByRole('row')
        .filter({ hasText: 'recipient@endgame.test' });
    await row.getByRole('button', { name: 'Suspend' }).click();
    await expect(row).toContainText('suspended');
});

test('verified account email change updates verification controls immediately', async ({
    page,
}) => {
    await login(page, 'manager');
    await page.getByRole('link', { name: 'Profile', exact: true }).click();
    await expect(
        page.getByRole('link', { name: 'Verify your email', exact: true }),
    ).toHaveCount(0);
    await page
        .getByLabel('Email', { exact: true })
        .fill('changed-manager@endgame.test');
    await page.getByRole('button', { name: 'Save profile' }).click();
    await expect(page.getByRole('status')).toContainText('Profile updated');
    await expect(
        page.getByRole('link', { name: 'Verify your email', exact: true }),
    ).toBeVisible();
    await page
        .getByRole('link', { name: 'Verify your email', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Verify your email' }),
    ).toBeVisible();
});

test('custom role matrix CRUD changes permissions for an existing session', async ({
    page,
    browser,
    baseURL,
}) => {
    await login(page, 'admin');
    await page.getByRole('link', { name: 'Browser team', exact: true }).click();
    await page
        .getByRole('button', { name: 'Create role', exact: true })
        .click();
    await page.getByLabel('Role name', { exact: true }).fill('Review access');
    await page.getByLabel('Role code', { exact: true }).fill('review-access');
    await expect(
        page.getByRole('columnheader', { name: 'Setup', exact: true }),
    ).toBeVisible();
    await page
        .getByRole('checkbox', {
            name: 'Activity log: View workspace activity',
            exact: true,
        })
        .check();
    await page.getByRole('button', { name: 'Save role', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Role created');
    const member = page
        .getByRole('row')
        .filter({ hasText: 'auditor@endgame.test' });
    await member.getByRole('button', { name: 'Change role' }).click();
    await page.getByRole('combobox', { name: 'Workspace role' }).click();
    await page
        .getByRole('option', { name: 'Review access', exact: true })
        .click();
    await page.getByRole('button', { name: 'Save member' }).click();
    await expect(member).toContainText('Review access');
    const recipientContext = await browser.newContext({ baseURL });
    const recipient = await recipientContext.newPage();
    await login(recipient, 'auditor');
    await recipient
        .getByRole('link', { name: 'Browser team', exact: true })
        .click();
    await expect(
        recipient.getByRole('heading', { name: 'Activity', exact: true }),
    ).toBeVisible();
    await expect(
        recipient.getByRole('heading', { name: /^Members/ }),
    ).toHaveCount(0);
    const role = page
        .getByRole('listitem')
        .filter({ hasText: 'Review access' });
    await role.getByRole('button', { name: 'Edit role', exact: true }).click();
    await page
        .getByRole('checkbox', {
            name: 'Activity log: View workspace activity',
            exact: true,
        })
        .uncheck();
    await page
        .getByRole('checkbox', {
            name: 'Workspace members: View members',
            exact: true,
        })
        .check();
    await page.getByRole('button', { name: 'Save role', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Role updated');
    await recipient.reload();
    await expect(
        recipient.getByRole('heading', { name: /^Members/ }),
    ).toBeVisible();
    await expect(
        recipient.getByRole('heading', { name: 'Activity', exact: true }),
    ).toHaveCount(0);
    await expect(
        recipient.getByRole('button', { name: 'Invite member', exact: true }),
    ).toHaveCount(0);
    await member.getByRole('button', { name: 'Remove member' }).click();
    await page.getByRole('button', { name: 'Confirm removal' }).click();
    await expect(page.getByText('auditor@endgame.test')).toHaveCount(0);
    await role
        .getByRole('button', { name: 'Delete role', exact: true })
        .click();
    await page.getByRole('button', { name: 'Confirm deletion' }).click();
    await expect(role).toHaveCount(0);
    const response = await recipient.reload();
    expect(response?.status()).toBe(403);
    await recipientContext.close();
});
