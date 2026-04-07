import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { defineConfig, devices } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function readAppUrlFromDotEnv() {
    const envPath = path.join(__dirname, '.env');

    if (!fs.existsSync(envPath)) {
        return null;
    }

    const envFile = fs.readFileSync(envPath, 'utf8');

    for (const line of envFile.split(/\r?\n/)) {
        const trimmed = line.trim();

        if (!trimmed || trimmed.startsWith('#') || !trimmed.startsWith('APP_URL=')) {
            continue;
        }

        return trimmed.slice('APP_URL='.length).replace(/^['"]|['"]$/g, '');
    }

    return null;
}

const baseURL =
    process.env.PLAYWRIGHT_BASE_URL ||
    process.env.APP_URL ||
    readAppUrlFromDotEnv() ||
    'http://127.0.0.1:8000';

const appUrl = new URL(baseURL);
const host = process.env.PLAYWRIGHT_HOST || appUrl.hostname;
const port = Number(
    process.env.PLAYWRIGHT_PORT ||
        appUrl.port ||
        (appUrl.protocol === 'https:' ? '443' : '80')
);
const isCI = Boolean(process.env.CI);
const webServerEnv = {
    ...process.env,
    MAIL_MAILER: process.env.PLAYWRIGHT_MAIL_MAILER || process.env.MAIL_MAILER || 'array',
};

export default defineConfig({
    testDir: './tests/browser',
    outputDir: 'test-results',
    fullyParallel: true,
    forbidOnly: isCI,
    retries: isCI ? 2 : 0,
    workers: isCI ? 1 : undefined,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL,
        headless: process.env.PLAYWRIGHT_HEADLESS !== 'false',
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
        video: 'retain-on-failure',
    },
    webServer: {
        command:
            process.env.PLAYWRIGHT_WEB_SERVER_COMMAND ||
            `php artisan serve --host=${host} --port=${port}`,
        env: webServerEnv,
        reuseExistingServer: !isCI,
        stderr: 'pipe',
        stdout: 'pipe',
        timeout: 120 * 1000,
        url: baseURL,
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
            },
        },
    ],
});
