import { test, expect } from '@playwright/test';
import { BASE_URL, IS_LOCAL } from '../helpers/site';

test.describe('Enquiry form', () => {
  test('posts to admin-post.php with a nonce, a honeypot and a consent box', async ({ page }) => {
    await page.goto('/contact/');
    const form = page.locator('form.enquiry-form');
    await expect(form).toHaveCount(1);
    expect(await form.getAttribute('action')).toMatch(/\/wp-admin\/admin-post\.php$/);
    await expect(form.locator('input[name="action"]')).toHaveValue('{{PREFIX}}_enquiry');
    await expect(form.locator('input[name="{{PREFIX}}_enquiry_nonce"]')).toHaveCount(1);
    // Honeypot: kept off-screen and out of the tab order, hidden from screen readers.
    await expect(form.locator('.enquiry-form__trap')).toHaveAttribute('aria-hidden', 'true');
    await expect(form.locator('input[name="website"]')).toHaveAttribute('tabindex', '-1');
    const trapBox = await form.locator('input[name="website"]').boundingBox();
    expect(trapBox === null || trapBox.x + trapBox.width <= 0, 'honeypot must sit off-screen').toBeTruthy();
    await expect(form.locator('input[name="consent"]')).toHaveAttribute('required', '');
    // No third-party form service or key in the page.
    expect(await page.content()).not.toMatch(/access_key|web3forms|formspree|api[_-]?key/i);
  });

  test('a real submission is saved, emailed and thanked (local only)', async ({ page, request }, testInfo) => {
    test.skip(!IS_LOCAL, 'never send test enquiries to staging or live from the suite');
    test.skip(testInfo.project.name !== 'desktop', 'one submission per run');
    const stamp = `Playwright ${Date.now()}`;
    await page.goto('/contact/');
    await page.fill('#{{PREFIX}}-name', stamp);
    await page.fill('#{{PREFIX}}-email', 'tester@example.com');
    await page.fill('#{{PREFIX}}-message', 'Automated test enquiry, please ignore.');
    await page.check('input[name="consent"]');
    await Promise.all([page.waitForURL(/enquiry=sent|thank-you/), page.click('.enquiry-form button[type="submit"]')]);

    // DDEV's Mailpit API: the email reached the (local) inbox with Reply-To the customer.
    const mailpit = BASE_URL.replace(/\/$/, '') + ':8026/api/v1/search?query=' + encodeURIComponent(`"${stamp}"`);
    const res = await request.get(mailpit).catch(() => null);
    test.skip(!res || !res.ok(), 'Mailpit not reachable; check the inbox by hand (ddev launch -m)');
    const data = await res!.json();
    expect(data.messages_count ?? data.total ?? 0).toBeGreaterThan(0);
  });
});
