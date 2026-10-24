import { expect, test, type Page } from '@playwright/test';
async function openBoard(page: Page) {
    await page.goto('/login');
    await page
        .getByLabel('Email', { exact: true })
        .fill('ticketmanager@endgame.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('browser-password-123');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.getByRole('link', { name: 'Ticket team', exact: true }).click();
    await page.getByRole('link', { name: 'Projects', exact: true }).click();
    await page
        .getByRole('link', { name: 'Product launch', exact: true })
        .click();
    await page.getByRole('link', { name: 'Board', exact: true }).click();
}
test('ticket dialog, details, transition, list/table and deletion', async ({
    page,
}) => {
    const errors: string[] = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await openBoard(page);
    await page.getByRole('button', { name: 'New ticket', exact: true }).click();
    const dialog = page.getByRole('dialog');
    await dialog
        .getByLabel('Title', { exact: true })
        .fill('Prepare release notes');
    await dialog
        .getByLabel('Description', { exact: true })
        .fill('Explain the changes clearly.');
    await dialog
        .getByRole('button', { name: 'Create ticket', exact: true })
        .click();
    await expect(
        page.getByText('Ticket created.', { exact: true }),
    ).toBeVisible();
    await page
        .getByRole('button', { name: 'Prepare release notes', exact: true })
        .click();
    const drawer = page.getByRole('dialog');
    await drawer
        .getByLabel('Title', { exact: true })
        .fill('Publish release notes');
    await drawer
        .getByRole('button', { name: 'Save changes', exact: true })
        .click();
    await expect(
        page.getByText('Ticket updated.', { exact: true }),
    ).toBeVisible();
    await drawer
        .getByRole('combobox', { name: 'Move to status', exact: true })
        .click();
    await page
        .getByRole('option', { name: 'In Progress', exact: true })
        .click();
    await expect(
        drawer.getByText('In Progress', { exact: true }).first(),
    ).toBeVisible();
    await drawer
        .getByRole('button', { name: 'Close ticket', exact: true })
        .click();
    await page.getByRole('link', { name: 'List', exact: true }).click();
    await expect(
        page.getByRole('button', {
            name: 'Publish release notes',
            exact: true,
        }),
    ).toBeVisible();
    await page.getByRole('link', { name: 'Table', exact: true }).click();
    await expect(page.getByRole('table')).toBeVisible();
    await page
        .getByRole('button', { name: 'Publish release notes', exact: true })
        .click();
    await drawer
        .getByRole('button', { name: 'Delete ticket', exact: true })
        .click();
    await drawer
        .getByRole('button', { name: 'Confirm delete', exact: true })
        .click();
    await expect(
        page.getByRole('button', {
            name: 'Publish release notes',
            exact: true,
        }),
    ).toHaveCount(0);
    expect(errors).toEqual([]);
});
test('board drag persists and workflow configuration controls available transitions', async ({
    page,
}) => {
    await openBoard(page);
    const card = page
        .locator('[data-ticket-id]')
        .filter({ hasText: 'Map the onboarding journey' });
    const target = page.locator(
        '[data-column-code="in_progress"] [data-dropzone]',
    );
    await card.hover();
    await page.mouse.down();
    await page.mouse.move(
        (await card.boundingBox())!.x + 30,
        (await card.boundingBox())!.y + 30,
        { steps: 5 },
    );
    const box = (await target.boundingBox())!;
    await page.mouse.move(box.x + 50, box.y + 30, { steps: 20 });
    await page.waitForTimeout(250);
    await page.mouse.up();
    await expect(
        page.locator('[data-column-code="in_progress"]'),
    ).toContainText('Map the onboarding journey');
    await page.reload();
    await expect(
        page.locator('[data-column-code="in_progress"]'),
    ).toContainText('Map the onboarding journey');
    await page.getByRole('link', { name: 'Workflow', exact: true }).click();
    await page
        .getByLabel('Status name 1', { exact: true })
        .fill('Ready to start');
    await page
        .getByRole('button', { name: 'Save workflow', exact: true })
        .click();
    await expect(
        page.getByText('Workflow saved.', { exact: true }),
    ).toBeVisible();
    await page.getByRole('link', { name: 'Board', exact: true }).click();
    await expect(
        page.getByText('Ready to start', { exact: true }).first(),
    ).toBeVisible();
});
test('mobile board and keyboard dialog preserve focus and fit the viewport', async ({
    page,
}) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await openBoard(page);
    await page.getByRole('button', { name: 'New ticket', exact: true }).focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: 'New ticket', exact: true }),
    ).toBeFocused();
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
    ).toBeTruthy();
});

test('a stale drawer save preserves the attempted title and inline error', async ({
    page,
}) => {
    await openBoard(page);
    await page
        .getByRole('button', { name: 'Polish the navigation', exact: true })
        .click();
    await expect(page).toHaveURL(/ticket=\d+/);
    const other = await page.context().newPage();
    await other.goto(page.url());
    await other
        .getByRole('dialog')
        .getByLabel('Title', { exact: true })
        .fill('Navigation updated elsewhere');
    await other
        .getByRole('dialog')
        .getByRole('button', { name: 'Save changes', exact: true })
        .click();
    await expect(
        other.getByText('Ticket updated.', { exact: true }),
    ).toBeVisible();
    await page
        .getByRole('dialog')
        .getByLabel('Title', { exact: true })
        .fill('Keep this unsaved title');
    await page
        .getByRole('dialog')
        .getByRole('button', { name: 'Save changes', exact: true })
        .click();
    await expect(
        page
            .getByRole('dialog')
            .getByText('This ticket changed. Reload before trying again.'),
    ).toBeVisible();
    await expect(
        page.getByRole('dialog').getByLabel('Title', { exact: true }),
    ).toHaveValue('Keep this unsaved title');
    await other.close();
});
