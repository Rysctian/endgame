import { defineConfig, devices } from '@playwright/test';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const php = process.platform === 'win32' && existsSync('.local/php/php.exe')
    ? `"${resolve('.local/php/php.exe')}"`
    : 'php';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    reporter: 'list',
    use: { baseURL: 'http://127.0.0.1:8001', trace: 'retain-on-failure' },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: `${php} artisan serve --host=127.0.0.1 --port=8001`,
        url: 'http://127.0.0.1:8001/up',
        reuseExistingServer: false,
        timeout: 30_000,
        env: { SESSION_DRIVER: 'file', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync' },
    },
});
