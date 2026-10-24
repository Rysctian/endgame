import { expect, test, type Page } from '@playwright/test';
async function login(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill('admin@endgame.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('browser-password-123');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/workspaces$/);
}
test('Geist loads and responsive navigation retains keyboard focus', async ({
    page,
}) => {
    await page.goto('/login');
    await page.evaluate(() => document.fonts.load('400 14px Geist'));
    expect(
        await page.evaluate(() => document.fonts.check('400 14px Geist')),
    ).toBeTruthy();
    await page.screenshot({
        path: test.info().outputPath('signin-desktop.png'),
    });
    await login(page);
    await page.screenshot({
        path: test.info().outputPath('workspaces-desktop.png'),
        fullPage: true,
    });
    await page.getByRole('link', { name: 'Ticket team', exact: true }).click();
    await page.getByRole('link', { name: 'Projects', exact: true }).click();
    await expect(
        page.getByRole('heading', { name: 'Projects', exact: true }),
    ).toBeVisible();
    await page.screenshot({
        path: test.info().outputPath('projects-desktop.png'),
        fullPage: true,
    });
    await page
        .getByRole('link', { name: 'Product launch', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'About', exact: true }),
    ).toBeVisible();
    await page.screenshot({
        path: test.info().outputPath('overview-desktop.png'),
        fullPage: true,
    });
    await page.getByRole('link', { name: 'Board', exact: true }).click();
    await expect(
        page.getByText('Map the onboarding journey', { exact: true }),
    ).toBeVisible();
    await page.screenshot({
        path: test.info().outputPath('board-desktop.png'),
        fullPage: true,
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await page
        .getByRole('button', { name: 'Open navigation', exact: true })
        .focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(
        page.getByRole('button', { name: 'Open navigation', exact: true }),
    ).toBeFocused();
    await page
        .getByRole('button', { name: 'Open navigation', exact: true })
        .click();
    await page.setViewportSize({ width: 390, height: 600 });
    const navigation = page.getByRole('dialog');
    const signOut = navigation.getByRole('button', {
        name: 'Sign out',
        exact: true,
    });
    await expect(signOut).toBeInViewport();
    const boardLink = navigation.getByRole('link', {
        name: 'Board navigation',
        exact: true,
    });
    await boardLink.focus();
    await page.keyboard.press('Tab');
    expect(
        await navigation
            .getByRole('link', { name: 'Table navigation', exact: true })
            .evaluate((el) => {
                el.focus();
                return getComputedStyle(el).boxShadow !== 'none';
            }),
    ).toBeTruthy();
    await page.screenshot({
        path: test.info().outputPath('navigation-mobile.png'),
    });
    await page.setViewportSize({ width: 1280, height: 800 });
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await page.setViewportSize({ width: 390, height: 844 });
    expect(
        await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
        ),
    ).toBeTruthy();
    await page.screenshot({
        path: test.info().outputPath('board-mobile.png'),
        fullPage: true,
    });
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.screenshot({
        path: test.info().outputPath('board-dark.png'),
        fullPage: true,
    });
    await page.evaluate(() =>
        document.documentElement.classList.remove('dark'),
    );
    await page.setViewportSize({ width: 1280, height: 800 });
    for (const screen of [
        {
            url: '/admin/users',
            heading: 'Accounts',
            file: 'accounts-desktop.png',
        },
        { url: '/profile', heading: 'Profile', file: 'profile-desktop.png' },
    ]) {
        await page.goto(screen.url);
        await expect(
            page.getByRole('heading', { name: screen.heading, exact: true }),
        ).toBeVisible();
        await page.screenshot({
            path: test.info().outputPath(screen.file),
            fullPage: true,
        });
    }
});

test('all accessible projects are visible and navigable from the shared sidebar', async ({
    page,
}) => {
    await login(page);
    await page.goto('/profile');
    const sidebar = page.getByRole('complementary', {
        name: 'Sidebar',
        exact: true,
    });
    for (const name of ['Design system', 'Mobile app', 'Product launch']) {
        await expect(
            sidebar.getByRole('link', {
                name: 'Open project ' + name,
                exact: true,
            }),
        ).toBeVisible();
    }
    await sidebar
        .getByRole('link', { name: 'Open project Mobile app', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Mobile app', exact: true }),
    ).toBeVisible();
    await sidebar
        .getByRole('link', { name: 'Open project Product launch', exact: true })
        .click();
    await expect(
        page.getByRole('heading', { name: 'Product launch', exact: true }),
    ).toBeVisible();
    await sidebar
        .getByRole('link', { name: 'Board navigation', exact: true })
        .click();
    for (const name of ['Design system', 'Mobile app', 'Product launch']) {
        await expect(
            sidebar.getByRole('link', {
                name: 'Open project ' + name,
                exact: true,
            }),
        ).toBeInViewport();
    }
    await expect(page).toHaveURL(/tickets\?view=board$/);
    await page.screenshot({
        path: test.info().outputPath('sidebar-projects-desktop.png'),
    });
    await page.setViewportSize({ width: 390, height: 600 });
    await page
        .getByRole('button', { name: 'Open navigation', exact: true })
        .click();
    const navigation = page.getByRole('dialog');
    await navigation
        .getByRole('link', { name: 'Open project Design system', exact: true })
        .click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
    await expect(
        page.getByRole('heading', { name: 'Design system', exact: true }),
    ).toBeVisible();
    await expect(
        page.getByText('Archived projects are read-only.', { exact: true }),
    ).toBeVisible();
});
