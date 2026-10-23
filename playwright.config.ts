import { defineConfig, devices } from '@playwright/test';
process.env.ENDGAME_E2E_PORT ??= String(
    20000 + Math.floor(Math.random() * 30000),
);
export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.ts',
    globalTimeout: 90_000,
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: 0,
    reporter: 'list',
    use: {
        baseURL: 'http://127.0.0.1:' + process.env.ENDGAME_E2E_PORT,
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
