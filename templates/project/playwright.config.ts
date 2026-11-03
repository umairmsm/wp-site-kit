import { defineConfig, devices } from '@playwright/test';

// Local DDEV by default. Staging or live: BASE_URL=https://… and TARGET_ENV=staging|production.
// Staging behind Cloudflare Access: CF_ACCESS_CLIENT_ID and CF_ACCESS_CLIENT_SECRET come from the
// environment (a git-ignored env file), never from this repo.
const baseURL = process.env.BASE_URL || 'https://{{SLUG}}.ddev.site';
const accessHeaders =
  process.env.CF_ACCESS_CLIENT_ID && process.env.CF_ACCESS_CLIENT_SECRET
    ? {
        'CF-Access-Client-Id': process.env.CF_ACCESS_CLIENT_ID,
        'CF-Access-Client-Secret': process.env.CF_ACCESS_CLIENT_SECRET,
      }
    : undefined;

export default defineConfig({
  testDir: './tests',
  testMatch: /.*\.spec\.ts/,
  fullyParallel: true,
  workers: process.env.PLAYWRIGHT_WORKERS ? Number(process.env.PLAYWRIGHT_WORKERS) : 2,
  // No automatic retries: a flaky test is a bug to fix or a blip to re-run by hand, never to hide.
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    extraHTTPHeaders: accessHeaders,
    ignoreHTTPSErrors: true, // DDEV's local certificate
    trace: 'retain-on-failure',
    screenshot: 'off',
    video: 'off',
    reducedMotion: 'reduce',
  },
  expect: {
    toHaveScreenshot: { maxDiffPixelRatio: 0.01, animations: 'disabled', fullPage: true },
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
    { name: 'mobile', use: { ...devices['Desktop Chrome'], viewport: { width: 375, height: 667 } } },
  ],
});
