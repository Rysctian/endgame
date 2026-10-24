import type { FullConfig } from '@playwright/test';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { execFileSync, spawn } from 'node:child_process';
import { once } from 'node:events';
import { createServer } from 'node:net';
import { randomUUID } from 'node:crypto';
import { setTimeout } from 'node:timers/promises';

export default async function setup(config: FullConfig) {
    const database = resolve(
        'storage/framework/testing/e2e-' + randomUUID() + '.sqlite',
    );
    mkdirSync(dirname(database), { recursive: true });
    writeFileSync(database, '');
    const php =
        process.platform === 'win32' ? resolve('.local/php/php.exe') : 'php';
    const env = {
        ...process.env,
        APP_ENV: 'testing',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: database,
        DB_URL: '',
        MAIL_MAILER: 'array',
        SESSION_DRIVER: 'file',
        CACHE_STORE: 'array',
        QUEUE_CONNECTION: 'sync',
        BCRYPT_ROUNDS: '4',
    };
    const options = { env, stdio: 'pipe' as const, windowsHide: true };
    execFileSync(php, ['tests/e2e/seed.php', '--check'], options);
    execFileSync(php, ['artisan', 'migrate', '--force'], options);
    execFileSync(php, ['tests/e2e/seed.php'], options);

    const address = new URL(config.projects[0].use.baseURL!);
    const probe = createServer();
    probe.listen(Number(address.port), address.hostname);
    await once(probe, 'listening');
    await new Promise<void>((resolve, reject) =>
        probe.close((error) => (error ? reject(error) : resolve())),
    );
    // Own the PHP process directly: Windows sandbox restrictions can block tree-wide taskkill.
    const server = spawn(
        php,
        [
            '-S',
            address.hostname + ':' + address.port,
            resolve('tests/e2e/server.php'),
        ],
        {
            cwd: resolve('public'),
            env,
            windowsHide: true,
            stdio: ['ignore', 'ignore', 'pipe'],
        },
    );
    let serverError: Error | undefined;
    let output = '';
    server.once('error', (error) => {
        serverError = error;
    });
    server.stderr.on('data', (data) => {
        output = (output + data).slice(-4000);
    });
    const stop = async () => {
        if (server.exitCode !== null || serverError) return;
        const exited = once(server, 'exit');
        server.kill();
        await exited;
    };
    try {
        for (let attempt = 0; attempt < 100; attempt++) {
            if (serverError) throw serverError;
            if (server.exitCode !== null)
                throw new Error('Test server exited: ' + output);
            try {
                const response = await fetch(new URL('/up', address), {
                    signal: AbortSignal.timeout(500),
                });
                if (response.ok) {
                    await setTimeout(100);
                    if (server.exitCode !== null)
                        throw new Error('Test server exited: ' + output);
                    return stop;
                }
            } catch (error) {
                if (server.exitCode !== null) throw error;
            }
            await setTimeout(100);
        }
        throw new Error('Test server did not become ready: ' + output);
    } catch (error) {
        await stop();
        throw error;
    }
}
