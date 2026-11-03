import { test, expect } from '@playwright/test';

// Server checks are the same on every viewport: run them once.
test.beforeEach(async ({}, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'server checks run once (desktop project)');
});

test.describe('Security basics', () => {
  test('pages send the security headers', async ({ request }) => {
    const h = (await request.get('/')).headers();
    expect(h['x-content-type-options']).toBe('nosniff');
    expect(h['x-frame-options']).toBe('SAMEORIGIN');
    expect(h['content-security-policy'] || '').toContain("frame-ancestors 'self'");
    expect(h['referrer-policy']).toBe('strict-origin-when-cross-origin');
    expect(h['x-pingback']).toBeUndefined();
  });

  test('no generator meta tag (hides the WordPress version)', async ({ request }) => {
    expect(await (await request.get('/')).text()).not.toMatch(/<meta name="generator"/i);
  });

  test('?author=1 never reveals a login name', async ({ request }) => {
    const res = await request.get('/?author=1', { maxRedirects: 0 });
    expect([301, 302, 404]).toContain(res.status());
    expect(res.headers()['location'] || '').not.toContain('/author/');
  });

  test('REST user list is closed to visitors', async ({ request }) => {
    expect((await request.get('/wp-json/wp/v2/users')).status()).toBe(401);
  });

  test('XML-RPC offers no pingbacks', async ({ request }) => {
    const res = await request.post('/xmlrpc.php', {
      data: '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>',
      headers: { 'Content-Type': 'text/xml' },
    });
    if (res.status() === 200) {
      expect(await res.text()).not.toContain('pingback.ping');
    } else {
      expect([403, 404, 405]).toContain(res.status()); // blocked at the server: even better
    }
  });

  // Uploads PHP block: not testable from here. A request for a missing uploads/*.php is answered
  // by WordPress (404) before the .htaccess rule runs. Test with a real file:
  // docs/reference/DEPLOY_HARDENING.md, section 1b.
});
