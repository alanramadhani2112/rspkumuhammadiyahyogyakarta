/**
 * RS PKU Settings admin UI contract tests.
 * Run: node tests/admin-css.test.mjs
 */

import { readFileSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import postcss from 'postcss';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CSS_PATH = join(__dirname, '..', 'assets', 'admin.css');
const JS_PATH = join(__dirname, '..', 'assets', 'admin.js');
const ADMIN_PHP_PATH = join(__dirname, '..', 'includes', 'class-rspku-settings-admin.php');
const REGISTRY_PHP_PATH = join(__dirname, '..', 'includes', 'class-rspku-settings-registry.php');

const css = readFileSync(CSS_PATH, 'utf-8');
const adminPhp = readFileSync(ADMIN_PHP_PATH, 'utf-8');
const registryPhp = readFileSync(REGISTRY_PHP_PATH, 'utf-8');
const adminJs = readFileSync(JS_PATH, 'utf-8');

let passed = 0;
let failed = 0;

function assert(condition, label) {
  if (condition) {
    console.log(`  ✓ ${label}`);
    passed++;
    return;
  }
  console.error(`  ✗ ${label}`);
  failed++;
}

console.log('\n📋 CSS Syntax');
try {
  postcss([]).process(css, { from: CSS_PATH }).root.walkRules(() => {});
  assert(true, 'CSS parses without syntax errors');
} catch (error) {
  assert(false, `CSS parse error: ${error.message}`);
}

console.log('\n📋 WordPress Native Shell');
const nativePhpContracts = [
  'class="wrap rspku-settings-wrap"',
  '<h1>RS PKU Settings</h1>',
  'class="rspku-settings-tabs nav-tab-wrapper"',
  'class="nav-tab <?php echo $active_tab === $tab_key ? \'nav-tab-active\' : \'\'; ?>"',
  'class="card rspku-settings-section"',
  'class="form-table" role="presentation"',
  'class="regular-text rspku-settings-input"',
  'class="large-text rspku-settings-textarea"',
  'class="button button-primary"',
  'class="button button-secondary"',
  'class="description',
  'class="rspku-settings-checkbox-label"',
  'class="rspku-settings-toggle-status"',
];
for (const needle of nativePhpContracts) assert(adminPhp.includes(needle), `Admin PHP contains ${needle}`);

assert(!adminPhp.includes('rspku-settings-header'), 'Custom gradient header markup removed');
assert(!adminPhp.includes('style='), 'Admin renderer has no inline styles');
assert(!adminPhp.includes('rs-'), 'Tailwind utility classes removed from renderer');

console.log('\n📋 Neutral CSS Shell');
assert(!css.includes('linear-gradient'), 'No gradient shell styling');
assert(!css.includes('.rspku-settings-header'), 'No custom header CSS');
assert(!css.includes('border-radius:999px') && !css.includes('border-radius: 999px'), 'No pill visual rules');
assert(!css.includes('button-primary{background') && !css.includes('.button-primary{background'), 'Core primary button color not overridden');
assert(!adminPhp.includes('class="card <?php echo esc_attr($cardClass); ?>"'), 'Nested custom field card shell removed');
assert(css.includes('.rspku-settings-section.card'), 'Section card hook styled around WP card');
assert(css.includes('.rspku-settings-section.is-collapsed .rspku-settings-section-body'), 'Collapse CSS retained');
assert(css.includes('.rspku-settings-checkbox-label input[type=checkbox]:checked~.rspku-settings-toggle-status .rspku-settings-toggle-status__on'), 'Native checkbox toggle status guard retained');

console.log('\n📋 Special Hooks Preserved');
const specialHooks = [
  '.rspku-settings-field--phone-pair',
  '.rspku-settings-field--cta-pair',
  '.rspku-call-pair',
  '.rspku-cta-pair',
  '.rspku-settings-card',
  '.rspku-settings-card--promo_card',
  '.rspku-settings-card--history_slot_card',
  '.rspku-repeater',
  '.rspku-repeater--hours',
  '.rspku-repeater-row--links',
  '.rspku-repeater-row--review',
  '.rspku-checkbox-picker',
  '.rspku-checkbox-picker-item',
  '.rspku-checkbox-picker-item.is-selected',
  '.rspku-image-upload',
  '.rspku-image-preview.hidden',
  '.rspku-post-picker',
  '.rspku-post-picker-dropdown',
  '.rspku-info-card',
  '.rspku-tools-grid',
  '.rspku-tools-card',
  '.rspku-settings-actions',
];
for (const selector of specialHooks) assert(css.includes(selector), `CSS keeps ${selector}`);

const jsHooks = [
  '.rspku-settings-form',
  '.rspku-settings-tabs .nav-tab',
  '.rspku-image-select',
  '.rspku-image-remove',
  '.rspku-repeater-add',
  '.rspku-repeater-add-link',
  '.rspku-repeater-add-review',
  '.rspku-settings-section-toggle',
  'is-collapsed',
];
for (const hook of jsHooks) assert(adminJs.includes(hook), `Admin JS keeps ${hook}`);
assert(adminJs.includes('class="regular-text"'), 'Dynamic repeater rows use native text inputs');
assert(adminJs.includes('class="large-text"'), 'Dynamic review textareas use native textarea class');

console.log('\n📋 Option Key Contracts');
const preservedKeys = [
  'phone_igd',
  'phone_igd_link',
  'phone_main',
  'phone_main_link',
  'whatsapp',
  'whatsapp_link',
  'hero_cta_primary_text',
  'hero_cta_primary_url',
  'hero_cta_secondary_text',
  'hero_cta_secondary_url',
  'home_cta_primary_text',
  'home_cta_primary_url',
  'home_cta_secondary_text',
  'home_cta_secondary_url',
  'home_featured_doctors',
];
for (const key of preservedKeys) {
  const matches = registryPhp.match(new RegExp(`'key'\\s*=>\\s*'${key}'`, 'g')) ?? [];
  assert(matches.length === 1, `Registry keeps exact field key ${key} once`);
}
assert(!registryPhp.includes("'key' => 'call_center'"), 'No replacement call_center option key');
assert(!registryPhp.includes("'type' => 'phone_pair'"), 'No replacement phone_pair type');
assert(!registryPhp.includes("'key' => 'homepage_cta_pair'"), 'No replacement homepage_cta_pair option key');
assert(!registryPhp.includes("'type' => 'cta_pair'"), 'No replacement cta_pair type');
assert(adminPhp.includes('function renderPhonePair'), 'Phone pair renderer preserved');
assert(adminPhp.includes('function renderCtaPair'), 'CTA pair renderer preserved');
assert(adminPhp.includes('function renderFieldCard'), 'Special card renderer preserved');
assert(adminPhp.includes('name="<?php echo esc_attr($name); ?>"'), 'Option array input names preserved');
assert(adminPhp.includes('<?php checked((bool) $value); ?>'), 'Toggle checked helper preserved');
assert(adminPhp.includes('<?php echo $describedBy;'), 'aria-describedby output preserved');

console.log('\n📋 Summary');
console.log(`Passed: ${passed}`);
console.log(`Failed: ${failed}`);
if (failed > 0) process.exit(1);
