import fs from 'fs';
import path from 'path';

export const BASE_URL = process.env.BASE_URL || 'https://{{SLUG}}.ddev.site';
// Say which environment you test when it isn't local: TARGET_ENV=staging or TARGET_ENV=production.
export const TARGET_ENV = process.env.TARGET_ENV || (/\.ddev\.site/.test(BASE_URL) ? 'local' : 'unknown');
export const IS_LOCAL = TARGET_ENV === 'local';
export const IS_PRODUCTION = TARGET_ENV === 'production';

// Every page the suites visit. Add each new template's URL here once.
export const PAGES: { path: string; name: string }[] = [
  { path: '/', name: 'home' },
  { path: '/contact/', name: 'contact' },
  { path: '/offers/', name: 'offers archive' },
];

export const ROOT = path.resolve(__dirname, '..', '..');
export const WP_CONTENT = path.join(ROOT, 'wordpress', '{{SLUG}}', 'web', 'wp-content');
export const CORE_DIR = path.join(WP_CONTENT, 'mu-plugins', '{{SLUG}}-core');
export const THEME_DIR = path.join(WP_CONTENT, 'themes', '{{SLUG}}-child');

// Text that means PHP printed an error into the page.
export const PHP_ERROR = /(Fatal error|Parse error|Warning: |Notice: |Deprecated: |There has been a critical error)/;

/** All files under dir whose names match the pattern. */
export function filesUnder(dir: string, pattern: RegExp): string[] {
  if (!fs.existsSync(dir)) return [];
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) return filesUnder(full, pattern);
    return pattern.test(entry.name) ? [full] : [];
  });
}
