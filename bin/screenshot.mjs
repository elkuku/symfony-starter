// Takes the readme screenshot (assets/docs/screenshot.png): the homepage in dark mode, logged in as `admin`.
// Run it with `composer screenshot`, which installs playwright-core into var/screenshot first.
// Needs the dev environment with fixtures loaded (bin/install) and Google Chrome.

import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { resolve } from 'node:path';

const require = createRequire(resolve('var/screenshot/package.json'));
const { chromium } = require('playwright-core');

const symfony = (...args) => execFileSync('symfony', [...args, '--no-ansi'], { encoding: 'utf8' });

const wasRunning = !symfony('server:status').includes('Not Running');
if (!wasRunning) {
    symfony('server:start', '-d');
}

const base = symfony('server:status').match(/Listening on (\S+)/)?.[1];
if (!base) {
    throw new Error('Could not find the URL of the Symfony web server.');
}

const browser = await chromium.launch({ channel: 'chrome' });
try {
    const page = await browser.newPage({
        viewport: { width: 1280, height: 800 },
        colorScheme: 'dark',
        ignoreHTTPSErrors: true,
    });

    await page.goto(`${base}/login`);
    await page.fill('#identifier', 'admin');
    await page.click('button:has-text("Sign in")');
    await page.waitForURL(`${base}/`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);
    await page.screenshot({ path: 'assets/docs/screenshot.png' });

    console.log('Saved assets/docs/screenshot.png');
} finally {
    await browser.close();
    if (!wasRunning) {
        symfony('server:stop');
    }
}
