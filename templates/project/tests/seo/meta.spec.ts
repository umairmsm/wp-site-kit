import { test, expect } from '@playwright/test';
import { PAGES, IS_PRODUCTION } from '../helpers/site';

test.describe('On-page SEO basics', () => {
  for (const p of PAGES) {
    test(`${p.name}: title, description, canonical, one H1, lang`, async ({ page }) => {
      await page.goto(p.path);
      const title = await page.title();
      expect(title.length, `title "${title}"`).toBeGreaterThanOrEqual(10);
      expect(title.length, `title "${title}"`).toBeLessThanOrEqual(65);

      // Read head tags directly: a locator would wait 30 s for a tag that is missing.
      const head = await page.evaluate(() => ({
        description: document.querySelector('meta[name="description"]')?.getAttribute('content') || '',
        canonical: document.querySelector('link[rel="canonical"]')?.getAttribute('href') || '',
        canonicalCount: document.querySelectorAll('link[rel="canonical"]').length,
      }));
      const { description, canonical } = head;
      expect(description.length, 'meta description length').toBeGreaterThanOrEqual(50);
      expect(description.length, 'meta description length').toBeLessThanOrEqual(160);
      expect(head.canonicalCount, 'exactly one canonical').toBe(1);

      expect(canonical, 'canonical must be an absolute URL').toMatch(/^https?:\/\//);

      await expect(page.locator('h1')).toHaveCount(1);
      expect(await page.locator('html').getAttribute('lang')).toBeTruthy();
    });
  }

  test('no request goes to Google Fonts', async ({ page }) => {
    const fontHosts: string[] = [];
    page.on('request', (req) => {
      if (/fonts\.(googleapis|gstatic)\.com/.test(req.url())) fontHosts.push(req.url());
    });
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    expect(fontHosts).toEqual([]);
  });
});

test.describe('Robots, sitemap and llms.txt', () => {
  test.beforeEach(async ({}, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop', 'server checks run once');
  });

  test('robots.txt matches the environment', async ({ request }) => {
    const body = await (await request.get('/robots.txt')).text();
    if (IS_PRODUCTION) {
      expect(body).not.toMatch(/Disallow:\s*\/\s*$/m);
      expect(body).toMatch(/Sitemap:/i);
    } else {
      expect(body).toMatch(/Disallow:\s*\/\s*$/m);
    }
  });

  test('a sitemap answers', async ({ request }) => {
    const index = await request.get('/sitemap_index.xml');
    const core = index.status() === 200 ? index : await request.get('/wp-sitemap.xml');
    expect(core.status()).toBe(200);
    expect(await core.text()).toContain('<sitemap');
  });

  test('/llms.txt is plain text and starts with the site name', async ({ request }) => {
    const res = await request.get('/llms.txt');
    expect(res.status()).toBe(200);
    expect(res.headers()['content-type']).toContain('text/plain');
    expect(await res.text()).toMatch(/^# \S/);
  });
});
