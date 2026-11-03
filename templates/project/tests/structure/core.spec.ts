import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { CORE_DIR, THEME_DIR, PAGES, PHP_ERROR, IS_PRODUCTION, filesUnder } from '../helpers/site';

// File checks don't need a browser: run them once.
test.beforeEach(async ({}, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'repo and server checks run once (desktop project)');
});

test.describe('Core plugin layout', () => {
  test('every module file is loaded, and every loaded module exists', () => {
    const bootstrap = fs.readFileSync(path.join(CORE_DIR, '{{SLUG}}-core.php'), 'utf8');
    const required = [...bootstrap.matchAll(/require_once __DIR__ \. '\/modules\/([a-z0-9-]+)\.php'/g)].map((m) => m[1]).sort();
    const onDisk = fs.readdirSync(path.join(CORE_DIR, 'modules')).filter((f) => f.endsWith('.php')).map((f) => f.replace(/\.php$/, '')).sort();
    expect(required).toEqual(onDisk);
  });

  test('the theme holds no business logic (plugin-first)', () => {
    // Queries, mail, post types, raw SQL and plugin APIs belong in the core plugin.
    const forbidden = /new WP_Query|get_posts\s*\(|\$wpdb|wp_mail\s*\(|register_post_type|register_post_meta|add_meta_box|\bpll_[a-z_]+\s*\(|wp_remote_(get|post)/;
    const offenders = filesUnder(THEME_DIR, /\.php$/).filter((f) => forbidden.test(fs.readFileSync(f, 'utf8')));
    expect(offenders.map((f) => path.relative(THEME_DIR, f))).toEqual([]);
  });

  test('no hard-coded prices in theme templates (prices come from the CMS)', () => {
    const price = /(€|£|\$)\s?\d|\b\d[\d.,]*\s?(EUR|USD|GBP)\b/;
    const offenders = filesUnder(THEME_DIR, /\.(php|html)$/).filter((f) => price.test(fs.readFileSync(f, 'utf8')));
    expect(offenders.map((f) => path.relative(THEME_DIR, f))).toEqual([]);
  });

  test('no Google Fonts anywhere in the theme (fonts are self-hosted)', () => {
    const offenders = filesUnder(THEME_DIR, /\.(php|css|js)$/).filter((f) => /fonts\.(googleapis|gstatic)\.com/.test(fs.readFileSync(f, 'utf8')));
    expect(offenders.map((f) => path.relative(THEME_DIR, f))).toEqual([]);
  });
});

test.describe('Pages render', () => {
  for (const page of PAGES) {
    test(`${page.name} (${page.path}) returns 200 with no PHP errors`, async ({ request }) => {
      const res = await request.get(page.path);
      expect(res.status()).toBe(200);
      const html = await res.text();
      expect(html).not.toMatch(PHP_ERROR);
      expect(html).toContain('</html>');
    });
  }

  test('outside production every response says noindex (core environment module is active)', async ({ request }) => {
    test.skip(IS_PRODUCTION, 'production must be indexable');
    const res = await request.get('/');
    expect(res.headers()['x-robots-tag'] || '').toContain('noindex');
  });
});
