import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { PAGES } from '../helpers/site';

// WCAG 2.1 AA. Serious and critical violations fail the build; the rest are attached to the
// report so they get fixed too. Never disable a rule to get green: fix the markup or the colours.
test.describe('Accessibility (axe, WCAG 2.1 AA)', () => {
  for (const p of PAGES) {
    test(`${p.name}: no serious or critical violations`, async ({ page }, testInfo) => {
      await page.goto(p.path, { waitUntil: 'networkidle' });
      const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
      await testInfo.attach('axe-violations.json', {
        body: JSON.stringify(results.violations, null, 2),
        contentType: 'application/json',
      });
      const blocking = results.violations
        .filter((v) => v.impact === 'serious' || v.impact === 'critical')
        .map((v) => `${v.id}: ${v.help} (${v.nodes.length} elements)`);
      expect(blocking).toEqual([]);
    });
  }
});
