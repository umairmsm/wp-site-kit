import { test, expect } from '@playwright/test';

// Screenshots differ between operating systems (fonts, anti-aliasing). Baselines are made and
// checked ONLY inside the pinned Playwright Docker image: `npm run test:visual:docker`
// (update with `npm run test:visual:update`, after the owner has approved the new look).
// Plain `npm test` skips this suite.
test.skip(!process.env.VISUAL, 'visual tests run only in the pinned Docker image (npm run test:visual:docker)');

test('home page looks the same as the approved baseline', async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => document.fonts.ready);
  // Load lazy images, then go back to the top.
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => window.scrollTo(0, 0));
  await expect(page).toHaveScreenshot('home.png', {
    mask: [page.locator('[data-visual-mask]')], // mark changing regions (dates, carousels) with data-visual-mask
  });
});
