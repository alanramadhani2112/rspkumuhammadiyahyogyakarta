/**
 * RS PKU Settings — Admin CSS Production Readiness Tests
 *
 * Run: node tests/admin-css.test.mjs
 * Exit 0 = all pass, Exit 1 = failure
 */

import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import postcss from 'postcss';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CSS_PATH = join(__dirname, '..', 'assets', 'admin.css');

let css;
try {
  css = readFileSync(CSS_PATH, 'utf-8');
} catch (e) {
  console.error('❌ FAIL: Cannot read assets/admin.css');
  process.exit(1);
}

let passed = 0;
let failed = 0;

function assert(condition, label) {
  if (condition) {
    console.log(`  ✓ ${label}`);
    passed++;
  } else {
    console.error(`  ✗ ${label}`);
    failed++;
  }
}

// ─── 1. CSS Syntax Validation ───
console.log('\n📋 CSS Syntax');
try {
  const result = postcss([]).process(css, { from: CSS_PATH });
  // Force parsing
  result.root.walk(() => {});
  assert(true, 'CSS parses without syntax errors');
} catch (e) {
  assert(false, `CSS parse error: ${e.message}`);
}

// ─── 2. Required Selectors Present ───
console.log('\n📋 Required Class Selectors');
const requiredSelectors = [
  '.rspku-settings-wrap',
  '.rspku-settings-header',
  '.rspku-settings-tabs',
  '.nav-tab',
  '.nav-tab-active',
  '.rspku-settings-section',
  '.rspku-settings-section-header',
  '.rspku-settings-section-body',
  '.rspku-settings-field',
  '.rspku-toggle',
  '.rspku-toggle-slider',
  '.rspku-repeater',
  '.rspku-repeater-row',
  '.rspku-repeater-row--links',
  '.rspku-repeater-row--review',
  '.rspku-checkbox-picker',
  '.rspku-checkbox-picker-grid',
  '.rspku-checkbox-picker-item',
  '.rspku-checkbox-picker-label',
  '.rspku-image-upload',
  '.rspku-image-preview',
  '.rspku-image-remove',
  '.rspku-image-select',
  '.rspku-info-card',
  '.rspku-tools-grid',
  '.rspku-tools-card',
  '.rspku-settings-actions',
];

for (const selector of requiredSelectors) {
  assert(css.includes(selector), `Contains "${selector}"`);
}

// ─── 3. Design Tokens ───
console.log('\n📋 Design Tokens');
const requiredTokens = [
  '--s-ink',
  '--s-sub',
  '--s-muted',
  '--s-line',
  '--s-bg',
  '--s-brand',
  '--s-brand-deep',
  '--s-brand-hover',
  '--s-brand-light',
  '--s-radius',
  '--s-radius-lg',
  '--s-font',
];

for (const token of requiredTokens) {
  assert(css.includes(token), `Token "${token}" defined`);
}

// ─── 4. Responsive Breakpoints ───
console.log('\n📋 Responsive Breakpoints');
assert(css.includes('@media (min-width: 1280px)'), 'Large desktop breakpoint (≥1280px)');
assert(css.includes('@media (min-width: 783px) and (max-width: 1024px)'), 'Tablet breakpoint (783–1024px)');
assert(css.includes('@media (max-width: 782px)'), 'Mobile breakpoint (≤782px)');

// ─── 5. Toggle Checkbox Exclusion ───
console.log('\n📋 Toggle/Checkbox Isolation');
assert(
  css.includes(':not([class*="rs-sr-only"])') && css.includes(':not([class*="rs-peer"])'),
  'Checkbox styles exclude sr-only/peer (toggle controller)'
);
assert(css.includes('rs-sr-only') && css.includes('!important'), 'sr-only rule uses !important to stay hidden');

// ─── 6. No Tailwind CDN ───
console.log('\n📋 Constraints');
assert(!css.includes('cdn.tailwindcss.com'), 'No Tailwind CDN reference');
assert(!css.includes('@tailwind'), 'No @tailwind directives');
assert(!css.includes('https://fonts.googleapis.com'), 'No external Google Fonts');

// ─── 7. !important Usage — limited and reasonable ───
const importantLines = css.split('\n').filter(l => l.includes('!important'));
// In WP admin, !important is necessary to override wp-admin.css specificity.
// We just check it's not excessive (< 40 instances) and not on random properties.
assert(
  importantLines.length < 40,
  `!important count is reasonable: ${importantLines.length} instances (< 40 allowed)`
);
// Verify none appear outside .rspku-settings-wrap context
const outsideWrap = importantLines.filter(l => {
  // Check if line is part of a non-plugin rule (very rough heuristic)
  return l.includes('body ') || l.includes('html ') || l.includes('#wpcontent');
});
assert(
  outsideWrap.length === 0,
  `!important not used on global selectors (no body/html/#wpcontent overrides)`
);

// ─── 8. Post Picker Grid Override ───
console.log('\n📋 Post Picker / Checkbox Picker');
assert(
  css.includes('[style*="grid-template-columns: repeat(auto-fill"]'),
  'Override for PHP inline grid-template-columns'
);
assert(
  css.includes('repeat(2, 1fr)'),
  'Forces 2-column grid for picker'
);
assert(
  css.includes('repeat(3, 1fr)'),
  'Forces 3-column grid on large desktop'
);

// ─── 9. Utility Fallbacks ───
console.log('\n📋 rs-* Utility Fallbacks');
const utilityPatterns = [
  'rs-grid',
  'rs-flex',
  'rs-items-center',
  'rs-gap-2',
  'rs-p-3',
  'rs-text-xs',
  'rs-bg-white',
  'rs-border',
  'rs-rounded',
  'rs-max-w-xl',
];
for (const u of utilityPatterns) {
  assert(css.includes(`"${u}"`), `Fallback for "${u}"`);
}

// ─── 10. File Size Check ───
console.log('\n📋 File Size');
const sizeKB = (Buffer.byteLength(css, 'utf-8') / 1024).toFixed(1);
assert(parseFloat(sizeKB) < 30, `File size reasonable: ${sizeKB} KB (< 30 KB)`);
assert(parseFloat(sizeKB) > 5, `File not empty/truncated: ${sizeKB} KB (> 5 KB)`);

// ─── Summary ───
console.log(`\n${'═'.repeat(50)}`);
console.log(`  Results: ${passed} passed, ${failed} failed`);
console.log(`${'═'.repeat(50)}\n`);

process.exit(failed > 0 ? 1 : 0);
