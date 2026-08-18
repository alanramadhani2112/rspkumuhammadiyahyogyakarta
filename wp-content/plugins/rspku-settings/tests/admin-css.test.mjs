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
const JS_PATH = join(__dirname, '..', 'assets', 'admin.js');
const ADMIN_PHP_PATH = join(__dirname, '..', 'includes', 'class-rspku-settings-admin.php');
const REGISTRY_PHP_PATH = join(__dirname, '..', 'includes', 'class-rspku-settings-registry.php');

let css;
try {
  css = readFileSync(CSS_PATH, 'utf-8');
} catch (e) {
  console.error('❌ FAIL: Cannot read assets/admin.css');
  process.exit(1);
}

let passed = 0;
let failed = 0;
const compactCss = css.replace(/\s+/g, '');

function assert(condition, label) {
  if (condition) {
    console.log(`  ✓ ${label}`);
    passed++;
  } else {
    console.error(`  ✗ ${label}`);
    failed++;
  }
}

function normalizeSelector(selector) {
  return selector.replace(/\[type=checkbox\]/g, '[type="checkbox"]');
}

const cssSelectors = new Set();

// ─── 1. CSS Syntax Validation ───
console.log('\n📋 CSS Syntax');
try {
  const result = postcss([]).process(css, { from: CSS_PATH });
  // Force parsing
  result.root.walkRules(rule => {
    for (const selector of rule.selector.split(',')) {
      cssSelectors.add(normalizeSelector(selector.trim()));
    }
  });
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
  '.rspku-settings-tab-label',
  '.rspku-settings-tab-count',
  '.rspku-settings-section',
  '.rspku-settings-section-header',
  '.rspku-settings-section-title-row',
  '.rspku-settings-section-title-group',
  '.rspku-settings-section-count',
  '.rspku-settings-section-completeness',
  '.rspku-settings-section-toggle',
  '.rspku-settings-section-body',
  '.rspku-settings-section.is-collapsed .rspku-settings-section-body',
  '.rspku-settings-field',
  '.rspku-settings-field__label',
  '.rspku-settings-field__control',
  '.rspku-settings-field__description',
  '.rspku-settings-field--phone-pair',
  '.rspku-settings-field--cta-pair',
  '.rspku-call-pair',
  '.rspku-call-pair__item',
  '.rspku-call-pair__label',
  '.rspku-call-pair__description',
  '.rspku-cta-pair',
  '.rspku-cta-pair__item',
  '.rspku-cta-pair__label',
  '.rspku-cta-pair__description',
  '.rspku-cta-pair__preview',
  '.rspku-cta-pair__preview-label',
  '.rspku-cta-pair__preview-empty',
  '.rspku-settings-card',
  '.rspku-settings-card__header',
  '.rspku-settings-card__title',
  '.rspku-settings-card__body',
  '.rspku-settings-card--promo_card',
  '.rspku-settings-card--history_slot_card',
  '.rspku-settings-input',
  '.rspku-settings-textarea',
  '.rspku-toggle',
  '.rspku-toggle-slider',
  '.rspku-repeater',
  '.rspku-repeater--hours',
  '.rspku-repeater-header',
  '.rspku-repeater-empty',
  '.rspku-repeater-row',
  '.rspku-repeater-row--hours',
  '.rspku-repeater-cell',
  '.rspku-repeater-cell__label',
  '.rspku-repeater-row--links',
  '.rspku-repeater-row--review',
  '.rspku-checkbox-picker',
  '.rspku-checkbox-picker-header',
  '.rspku-checkbox-picker-hint',
  '.rspku-checkbox-picker-count',
  '.rspku-checkbox-picker-grid',
  '.rspku-checkbox-picker-item',
  '.rspku-checkbox-picker-item.is-selected',
  '.rspku-checkbox-picker-label',
  '.rspku-image-upload',
  '.rspku-image-preview',
  '.rspku-image-preview-img',
  '.rspku-image-status',
  '.rspku-image-empty',
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
console.log('\n📋 Design System Values');
const requiredDesignValues = [
  '#0c8f45',
  '#065f2e',
  '#0f172a',
  '#475569',
  '#e2e8f0',
];

for (const value of requiredDesignValues) {
  assert(css.includes(value), `Design value "${value}" present`);
}

// ─── 3b. Call Center Key Contract ───
console.log('\n📋 Call Center Field Contract');
const registryPhp = readFileSync(REGISTRY_PHP_PATH, 'utf-8');
const pairAdminPhp = readFileSync(ADMIN_PHP_PATH, 'utf-8');
const callCenterKeys = ['phone_igd', 'phone_igd_link', 'phone_main', 'phone_main_link', 'whatsapp', 'whatsapp_link'];

for (const key of callCenterKeys) {
  const keyDefinitionMatches = registryPhp.match(new RegExp(`'key'\\s*=>\\s*'${key}'`, 'g')) ?? [];
  assert(keyDefinitionMatches.length === 1, `Registry keeps exact field key "${key}" once`);
  assert(pairAdminPhp.includes(`name="<?php echo esc_attr($name); ?>"`), 'Admin renderer keeps option array input names');
}
assert(!registryPhp.includes("'key' => 'call_center'"), 'No replacement call_center option key');
assert(!registryPhp.includes("'type' => 'phone_pair'"), 'No replacement phone_pair field type');
assert((registryPhp.match(/'group'\s*=>\s*'call_center'/g) ?? []).length === 6, 'Six existing phone fields declare call_center metadata');

// ─── 3c. Homepage CTA Key Contract ───
console.log('\n📋 Homepage CTA Field Contract');
const homepageCtaKeys = [
  'hero_cta_primary_text',
  'hero_cta_primary_url',
  'hero_cta_secondary_text',
  'hero_cta_secondary_url',
  'home_cta_primary_text',
  'home_cta_primary_url',
  'home_cta_secondary_text',
  'home_cta_secondary_url',
];

for (const key of homepageCtaKeys) {
  const keyDefinitionMatches = registryPhp.match(new RegExp(`'key'\\s*=>\\s*'${key}'`, 'g')) ?? [];
  assert(keyDefinitionMatches.length === 1, `Registry keeps exact field key "${key}" once`);
  assert(new RegExp(`'key'\\s*=>\\s*'${key}'[^\n]+?'type'\\s*=>\\s*'text'`).test(registryPhp), `Registry keeps "${key}" as text field`);
}
assert(!registryPhp.includes("'key' => 'homepage_cta_pair'"), 'No replacement homepage_cta_pair option key');
assert(!registryPhp.includes("'type' => 'cta_pair'"), 'No replacement cta_pair field type');
assert((registryPhp.match(/'group'\s*=>\s*'homepage_cta_pair'/g) ?? []).length === 8, 'Eight Homepage CTA fields declare pair metadata');
assert(!registryPhp.includes("'group' => 'header_cta_pair'"), 'Header CTA fields stay outside Homepage CTA pair metadata');
assert(pairAdminPhp.includes('function isCtaPairStart'), 'Admin renderer gates CTA pairs by metadata');
assert(pairAdminPhp.includes('function renderCtaPair'), 'Admin renderer has CTA pair renderer');
assert(pairAdminPhp.includes('name="<?php echo esc_attr($name); ?>"'), 'CTA renderer keeps option array input names');

// ─── 3c2. Homepage Post Picker Contract ───
console.log('\n📋 Homepage Post Picker Contract');
const postPickerKeys = ['home_featured_services', 'home_featured_doctors'];
const postPickerAdminPhp = pairAdminPhp.slice(
  pairAdminPhp.indexOf("$type === 'post_picker'"),
  pairAdminPhp.indexOf("$type === 'review_repeater'")
);
const legacyPostPickerJs = readFileSync(JS_PATH, 'utf-8').slice(
  readFileSync(JS_PATH, 'utf-8').indexOf('// Post picker: search + select'),
  readFileSync(JS_PATH, 'utf-8').indexOf('// Dismiss dropdowns')
);

for (const key of postPickerKeys) {
  const keyDefinitionMatches = registryPhp.match(new RegExp(`'key'\\s*=>\\s*'${key}'`, 'g')) ?? [];
  assert(keyDefinitionMatches.length === 1, `Registry keeps exact post picker key "${key}" once`);
  assert(new RegExp(`'key'\\s*=>\\s*'${key}'[^\n]+?'type'\\s*=>\\s*'post_picker'`).test(registryPhp), `Registry keeps "${key}" as post_picker`);
  assert(new RegExp(`'key'\\s*=>\\s*'${key}'[^\n]+?'max'\\s*=>\\s*6`).test(registryPhp), `Registry keeps "${key}" max 6`);
}
assert(postPickerAdminPhp.includes('rspku-checkbox-picker'), 'Post picker renderer uses checkbox picker wrapper');
assert(postPickerAdminPhp.includes('rspku-checkbox-picker-count'), 'Post picker renderer shows available count');
assert(postPickerAdminPhp.includes('rspku-checkbox-picker-item<?php echo $isSelected ? \' is-selected\' : \'\'; ?>'), 'Post picker renderer marks selected labels');
assert(postPickerAdminPhp.includes('Pilih maksimal') === false, 'Post picker hint no longer hides selected count in generic copy');
assert(postPickerAdminPhp.includes('<?php echo count($selectedIds); ?> terpilih dari maksimal <?php echo $maxItems; ?> item'), 'Post picker hint includes selected and max count');
assert(postPickerAdminPhp.includes('name="<?php echo esc_attr($name); ?>[]"'), 'Post picker keeps checkbox array input name shape');
assert((postPickerAdminPhp.match(/type="checkbox"/g) ?? []).length === 1, 'Post picker renderer keeps one checkbox template path');
assert(!postPickerAdminPhp.includes('type="hidden"'), 'Post picker renderer does not replace checkboxes with hidden value field');
assert(!postPickerAdminPhp.includes('rspku-post-picker-value'), 'Post picker renderer does not use legacy comma-value selector');
assert(!postPickerAdminPhp.includes('json_encode'), 'Post picker renderer does not serialize selection to JSON');
assert(!postPickerAdminPhp.includes('implode('), 'Post picker renderer does not serialize selection to comma string');
assert(legacyPostPickerJs.includes('.rspku-post-picker-value'), 'Legacy JS comma-value picker remains isolated to legacy selector');

// ─── 3d. Promo Card + History Slot Field Contract ───
console.log('\n📋 Promo Card + History Slot Field Contract');
const promoKeys = [
  'promo_slide_1_enabled',
  'promo_slide_1_image_id',
  'promo_slide_1_title',
  'promo_slide_1_description',
  'promo_slide_1_cta_text',
  'promo_slide_1_cta_url',
  'promo_slide_2_enabled',
  'promo_slide_2_image_id',
  'promo_slide_2_title',
  'promo_slide_2_description',
  'promo_slide_2_cta_text',
  'promo_slide_2_cta_url',
  'promo_slide_3_enabled',
  'promo_slide_3_image_id',
  'promo_slide_3_title',
  'promo_slide_3_description',
  'promo_slide_3_cta_text',
  'promo_slide_3_cta_url',
];
const historySlots = ['history_hero', 'history_pioneers', 'history_child_service', 'history_first_stone', 'history_modernization'];
const historyKeys = historySlots.flatMap((slot) => [`${slot}_image_id`, `${slot}_year`, `${slot}_title`, `${slot}_caption`, `${slot}_alt`]);

for (const key of [...promoKeys, ...historyKeys]) {
  const keyDefinitionMatches = registryPhp.match(new RegExp(`'key'\\s*=>\\s*'${key}'`, 'g')) ?? [];
  assert(keyDefinitionMatches.length === 1, `Registry keeps exact field key "${key}" once`);
}
assert((registryPhp.match(/'group'\s*=>\s*'promo_card'/g) ?? []).length === 18, 'Eighteen promo fields declare promo_card metadata');
assert((registryPhp.match(/'card_role'\s*=>\s*'start'/g) ?? []).filter((match) => match).length >= 8, 'Card metadata declares starts for promo cards and history slots');
assert((registryPhp.match(/'group'\s*=>\s*'history_slot_card'/g) ?? []).length === 25, 'Twenty-five history fields declare history_slot_card metadata');
assert(!registryPhp.includes("'key' => 'promo_card'"), 'No replacement promo_card option key');
assert(!registryPhp.includes("'key' => 'history_card'"), 'No replacement history_card option key');
assert(!registryPhp.includes("'type' => 'promo_card'"), 'No replacement promo_card field type');
assert(!registryPhp.includes("'type' => 'history_card'"), 'No replacement history_card field type');
assert(pairAdminPhp.includes('function isCardStart'), 'Admin renderer gates cards by metadata');
assert(pairAdminPhp.includes('function renderFieldCard'), 'Admin renderer has field card renderer');
assert(pairAdminPhp.includes('rspku-image-upload'), 'Card renderer keeps image fields on existing image picker path');
assert(readFileSync(JS_PATH, 'utf-8').includes("'.rspku-image-select'"), 'Image picker select selector still present');
assert(readFileSync(JS_PATH, 'utf-8').includes("'.rspku-image-remove'"), 'Image picker remove selector still present');

// ─── 3e. Image Picker Contract ───
console.log('\n📋 Image Picker Contract');
const imageAdminPhp = pairAdminPhp.slice(
  pairAdminPhp.indexOf("$type === 'image'"),
  pairAdminPhp.indexOf("$type === 'info'")
);
const imageJs = readFileSync(JS_PATH, 'utf-8');
const imageSelectJs = imageJs.slice(
  imageJs.indexOf("'.rspku-image-select'"),
  imageJs.indexOf('// Image remove')
);
const imageRemoveJs = imageJs.slice(
  imageJs.indexOf("'.rspku-image-remove'"),
  imageJs.indexOf('// Repeater: add row')
);
assert(imageAdminPhp.includes('class="rspku-image-upload"'), 'Image renderer preserves upload wrapper selector');
assert(imageAdminPhp.includes('class="rspku-image-preview'), 'Image renderer preserves preview selector');
assert(imageAdminPhp.includes('class="rspku-image-preview-img"'), 'Image renderer uses preview image selector consumed by JS');
assert(imageAdminPhp.includes('type="hidden" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $image_id); ?>"'), 'Image renderer keeps one hidden input with original id/name/value contract');
assert((imageAdminPhp.match(/type="hidden"/g) ?? []).length === 1, 'Image renderer emits exactly one hidden input');
assert(imageSelectJs.includes('wp.media({'), 'Image select keeps WordPress media library flow');
assert(imageSelectJs.includes("title: 'Pilih Gambar'"), 'Image select keeps media modal title');
assert(imageSelectJs.includes("button: { text: 'Gunakan Gambar Ini' }"), 'Image select keeps media button label');
assert(imageSelectJs.includes('multiple: false'), 'Image select keeps single image selection');
assert(imageSelectJs.includes("library: { type: 'image' }"), 'Image select keeps image-only media library');
assert(imageSelectJs.includes('$input.val(attachment.id)'), 'Image select stores attachment ID only');
assert(imageSelectJs.includes("$img.attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url)"), 'Image select updates preview image src');
assert(imageSelectJs.includes("$preview.removeClass('hidden')"), 'Image select reveals preview');
assert(imageSelectJs.includes("$empty.addClass('hidden')"), 'Image select hides empty state');
assert(imageRemoveJs.includes('$container.find(\'input[type="hidden"]\').val(\'0\')'), 'Image remove sets saved value to 0');
assert(imageRemoveJs.includes(".find('.rspku-image-preview-img').attr('src', '')"), 'Image remove clears stale preview image src');
assert(imageRemoveJs.includes(".find('.rspku-image-preview').addClass('hidden')"), 'Image remove hides preview');
assert(imageRemoveJs.includes(".find('.rspku-image-empty').removeClass('hidden')"), 'Image remove shows empty state');
assert(imageRemoveJs.includes(".find('.rspku-image-select').removeClass('hidden')"), 'Image remove restores select action');

// ─── 3f. Jam Operasional Repeater Contract ───
console.log('\n📋 Jam Operasional Repeater Contract');
const repeaterAdminPhp = pairAdminPhp.slice(
  pairAdminPhp.indexOf("$type === 'repeater_hours'"),
  pairAdminPhp.indexOf("$type === 'repeater_links'")
);
const hoursJs = readFileSync(JS_PATH, 'utf-8');
const addRowTemplate = hoursJs.slice(
  hoursJs.indexOf("'.rspku-repeater-add'"),
  hoursJs.indexOf('// Repeater: remove row')
);
function nextHoursIndexFromNames(names) {
  let maxIndex = -1;

  for (const name of names) {
    const match = String(name || '').match(/\[(\d+)]\[(label|time|highlight)]$/);
    if (match) maxIndex = Math.max(maxIndex, Number(match[1]));
  }

  return maxIndex + 1;
}
assert(registryPhp.includes("'key' => 'service_hours', 'label' => 'Jam Operasional', 'type' => 'repeater_hours'"), 'Registry keeps service_hours repeater_hours field');
assert(repeaterAdminPhp.includes('name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][label]"'), 'Existing rows keep [label] input name shape');
assert(repeaterAdminPhp.includes('name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][time]"'), 'Existing rows keep [time] input name shape');
assert(repeaterAdminPhp.includes('name="<?php echo esc_attr($name); ?>[<?php echo $i; ?>][highlight]"'), 'Existing rows keep [highlight] input name shape');
assert(addRowTemplate.includes('name="${name}[${index}][label]"'), 'Add-row template keeps [label] input name shape');
assert(addRowTemplate.includes('name="${name}[${index}][time]"'), 'Add-row template keeps [time] input name shape');
assert(addRowTemplate.includes('name="${name}[${index}][highlight]"'), 'Add-row template keeps [highlight] input name shape');
assert(addRowTemplate.includes('rspku-repeater-row--hours'), 'Add-row template uses hours row class');
assert(addRowTemplate.includes("$container.find('.rspku-repeater-empty').remove()"), 'Add-row template clears hours empty state');
assert(hoursJs.includes('function getNextRepeaterIndex($container, name)'), 'Hours add-row has max-index helper');
assert(addRowTemplate.includes('const index = getNextRepeaterIndex($container, name);'), 'Hours add-row uses next max index, not row count');
assert(!addRowTemplate.includes("find('.rspku-repeater-row').length"), 'Hours add-row does not use row count as index');
assert(nextHoursIndexFromNames([
  'rspku_settings[service_hours][0][label]',
  'rspku_settings[service_hours][0][time]',
  'rspku_settings[service_hours][2][highlight]',
]) === 3, 'Sparse hours indices 0 and 2 produce next index 3');
assert(hoursJs.includes("'.rspku-repeater-add'"), 'Add selector .rspku-repeater-add stays stable');
assert(hoursJs.includes("'.rspku-repeater-remove'"), 'Remove selector .rspku-repeater-remove stays stable');
assert(hoursJs.includes("$container.hasClass('rspku-repeater--hours')"), 'Remove handler restores empty state only for hours repeater');
assert(!repeaterAdminPhp.includes('json_encode'), 'Hours renderer does not replace rows with JSON');
assert(!addRowTemplate.includes('JSON.stringify'), 'Hours add-row template does not replace rows with object serialization');

// ─── 4. Responsive Breakpoints ───
console.log('\n📋 Responsive Breakpoints');
assert(compactCss.includes('@media(max-width:782px)'), 'Mobile breakpoint (≤782px)');
assert(compactCss.includes('.rspku-repeater-row--hours{grid-template-columns:1fr'), 'Hours repeater stacks to one column by default for 360px safety');
assert(compactCss.includes('@media(min-width:600px)'), 'Hours repeater only switches to columns above 600px');

// ─── 4b. Actions Bar Is Not Overlay ───
console.log('\n📋 Actions Bar Document Flow');
const actionsRules = [];
try {
  const result = postcss([]).process(css, { from: CSS_PATH });
  result.root.walkRules('.rspku-settings-actions', rule => actionsRules.push(rule));
} catch (e) {
  // Syntax validation above reports parser failures; keep this section assertion-based.
}
const actionsDecls = actionsRules.flatMap(rule => rule.nodes?.filter(node => node.type === 'decl') ?? []);
const actionsProps = new Map(actionsDecls.map(decl => [decl.prop, decl.value]));
assert(actionsRules.length > 0, '.rspku-settings-actions rule exists');
assert(actionsProps.get('position') !== 'sticky', '.rspku-settings-actions does not use sticky positioning');
assert(!actionsProps.has('bottom'), '.rspku-settings-actions does not pin to viewport bottom');
assert(!actionsProps.has('z-index'), '.rspku-settings-actions does not create overlay stacking');

// ─── 4c. Field Contract Widths ───
console.log('\n📋 Field Contract Widths');
const fieldControlRules = [];
try {
  const result = postcss([]).process(css, { from: CSS_PATH });
  result.root.walkRules(rule => {
    if (rule.selector.includes('.rspku-settings-input') || rule.selector.includes('.rspku-settings-textarea')) {
      fieldControlRules.push(rule);
    }
  });
} catch (e) {
  // Syntax validation above reports parser failures; keep this section assertion-based.
}
const fieldControlDecls = fieldControlRules.flatMap(rule => rule.nodes?.filter(node => node.type === 'decl') ?? []);
const fieldControlProps = new Map(fieldControlDecls.map(decl => [decl.prop, decl.value]));
assert(fieldControlRules.length > 0, 'Default field control rule exists');
assert(fieldControlProps.get('width') === '100%', 'Default text controls fill their container');
assert(fieldControlProps.get('max-width') === 'none', 'Default text controls have no global max-width cap');
assert(!css.includes('rs-max-w-lg'), 'Generated CSS does not include stale rs-max-w-lg utility');
assert(!css.includes('max-width: 520px'), 'Generated CSS has no 520px field cap');

// ─── 4d. Navigation / Section Progressive Enhancement ───
console.log('\n📋 Navigation Sections Contract');
const sectionBodyBaseRules = [];
const sectionCollapsedRules = [];
try {
  const result = postcss([]).process(css, { from: CSS_PATH });
  result.root.walkRules(rule => {
    if (rule.selector === '.rspku-settings-section-body') {
      sectionBodyBaseRules.push(rule);
    }
    if (rule.selector === '.rspku-settings-section.is-collapsed .rspku-settings-section-body') {
      sectionCollapsedRules.push(rule);
    }
  });
} catch (e) {
  // Syntax validation above reports parser failures; keep this section assertion-based.
}
const sectionBodyBaseDecls = sectionBodyBaseRules.flatMap(rule => rule.nodes?.filter(node => node.type === 'decl') ?? []);
const sectionBodyBaseProps = new Map(sectionBodyBaseDecls.map(decl => [decl.prop, decl.value]));
const sectionCollapsedDecls = sectionCollapsedRules.flatMap(rule => rule.nodes?.filter(node => node.type === 'decl') ?? []);
const sectionCollapsedProps = new Map(sectionCollapsedDecls.map(decl => [decl.prop, decl.value]));
assert(sectionBodyBaseRules.length > 0, 'Base section body rule exists for JS-disabled visibility');
assert(sectionBodyBaseProps.get('display') !== 'none', 'Base section body is not hidden without JS');
assert(sectionCollapsedProps.get('display') === 'none', 'Collapsed class hides body only after JS toggle');

let adminJs = '';
let adminPhp = '';
try {
  adminJs = readFileSync(JS_PATH, 'utf-8');
  adminPhp = readFileSync(ADMIN_PHP_PATH, 'utf-8');
} catch (e) {
  assert(false, `Cannot read admin JS/PHP source: ${e.message}`);
}
assert(adminJs.includes("'.rspku-settings-section-toggle'"), 'Section toggle JS binds only to collapse button');
assert(adminJs.includes("toggleClass('is-collapsed', collapsed)"), 'Section toggle only changes collapse class');
assert(adminJs.includes("$button.attr('aria-expanded', collapsed ? 'false' : 'true')"), 'Collapse JS synchronizes aria-expanded state');
const collapseHandler = adminJs.slice(
  adminJs.indexOf("'.rspku-settings-section-toggle'"),
  adminJs.indexOf('// Hide dropdown on outside click')
);
assert(!/\.remove\(\)|\.detach\(\)|\.prop\(['"]disabled['"]/.test(collapseHandler), 'Collapse JS does not remove, detach, or disable inputs');
assert(adminPhp.includes('aria-current="page"'), 'Active tab exposes aria-current without changing routing');
assert(adminPhp.includes('name="active_tab"'), 'Hidden active_tab save contract remains rendered');
assert(adminPhp.includes('data-section-key='), 'Section wrapper exposes stable registry key metadata');
assert(adminPhp.includes('$descriptionId = $id . \'-description\''), 'Field descriptions use deterministic IDs from input IDs');
assert(adminPhp.includes('$describedBy = $help ?'), 'Standard field controls build aria-describedby only when help exists');
assert(adminPhp.includes('id="<?php echo esc_attr($descriptionId); ?>" class="rspku-settings-field__description"'), 'Rendered standard help paragraph exposes the deterministic description ID');
assert(adminPhp.includes('aria-describedby="<?php echo esc_attr($descriptionId); ?>"'), 'Pair inputs connect help text with aria-describedby');
assert(adminPhp.includes('aria-label="Pilih gambar <?php echo esc_attr((string) $field[\'label\']); ?> dari Media Library"'), 'Image picker button has contextual aria-label');
assert(adminPhp.includes('aria-label="Hapus gambar <?php echo esc_attr((string) $field[\'label\']); ?>"'), 'Image remove button has contextual aria-label');
assert(adminPhp.includes('aria-label="Label link cepat"'), 'Repeater link label input has accessible name');
assert(adminPhp.includes('aria-label="Rating ulasan"'), 'Review repeater rating select has accessible name');
assert(adminJs.includes('aria-label="Label link cepat"'), 'Dynamic link repeater rows keep accessible names');
assert(adminJs.includes('aria-label="Rating ulasan"'), 'Dynamic review repeater rows keep accessible names');

console.log('\n📋 Unsaved Change Feedback Contract');
assert(adminJs.includes("let settingsDirty = false"), 'Settings JS tracks dirty state locally only');
assert(adminJs.includes("'.rspku-settings-form'") && adminJs.includes("'input change'") && adminJs.includes("':input'"), 'Settings form fields mark form dirty on input/change');
assert(adminJs.includes("'beforeunload'"), 'Unsaved dirty form uses native beforeunload feedback');
assert(adminJs.includes("'.rspku-settings-tabs .nav-tab'") && adminJs.includes('window.confirm(unsavedMessage)'), 'Settings tab clicks confirm when form is dirty');
assert(/\.on\('submit', function \(\) \{\s*settingsDirty = false;\s*\}\)/.test(adminJs), 'Settings submit clears dirty bypass before normal save');
assert(!/\.rspku-settings-form[\s\S]{0,120}submit[\s\S]{0,120}preventDefault/.test(adminJs), 'Settings form submit is not prevented');

console.log('\n📋 Completeness Feedback Contract');
const saveRendererPhp = adminPhp.slice(
  adminPhp.indexOf('private static function renderTabContent'),
  adminPhp.indexOf('private static function countSectionEmptyFields')
);
const sanitizerPhp = adminPhp.slice(
  adminPhp.indexOf('public static function sanitize'),
  adminPhp.indexOf('/**\n     * Return true when a tab owns a field')
);
assert(adminPhp.includes('private static function countSectionEmptyFields'), 'Admin renderer computes section completeness on the server');
assert(adminPhp.includes('private static function isCompletenessEmpty'), 'Admin renderer isolates presentational empty-field detection');
assert(adminPhp.includes('rspku-settings-section-completeness'), 'Admin renderer outputs section completeness feedback markup');
assert(adminPhp.includes('Tetap bisa disimpan'), 'Completeness copy states save remains allowed');
assert(!saveRendererPhp.includes(' required'), 'Settings renderer does not add required attributes');
assert(!/\.rspku-settings-form[\s\S]{0,120}submit[\s\S]{0,120}preventDefault/.test(adminJs), 'Settings form has no submit-blocking JS');
assert(sanitizerPhp.includes('} elseif (self::isUrlField($key)) {'), 'Sanitizer keeps URL field branch as source of truth');
assert(sanitizerPhp.includes("str_starts_with($key, 'promo_slide_') ? self::sanitizePromoUrl((string) $value) : esc_url_raw((string) $value)"), 'URL fields keep existing esc_url_raw/promo sanitizer path');
assert(sanitizerPhp.includes('sanitize_text_field((string) $value)'), 'Optional text fields keep existing sanitizer semantics');

console.log('\n📋 Focus Visibility');
const focusVisibleSelectors = [
  '.rspku-settings-tabs .nav-tab:focus-visible',
  '.rspku-settings-section-toggle:focus-visible',
  '.rspku-settings-actions .button:focus-visible',
  '.rspku-image-select:focus-visible',
  '.rspku-image-remove:focus-visible',
  '.rspku-repeater-add:focus-visible',
  '.rspku-repeater-remove:focus-visible',
  '.rspku-checkbox-picker-item:focus-within',
  '.rspku-checkbox-picker-item input[type="checkbox"]:focus-visible',
];
for (const selector of focusVisibleSelectors) {
  assert(cssSelectors.has(selector), `Focus selector present: ${selector}`);
}

// ─── 5. No Tailwind CDN ───
console.log('\n📋 Constraints');
assert(!css.includes('cdn.tailwindcss.com'), 'No Tailwind CDN reference');
assert(!css.includes('@tailwind'), 'No @tailwind directives');
assert(!css.includes('https://fonts.googleapis.com'), 'No external Google Fonts');

// ─── 6. !important Usage — limited and reasonable ───
const importantLines = css.split('\n').filter(l => l.includes('!important'));
// In WP admin, !important is necessary to override wp-admin.css specificity.
// We just check it's not excessive (< 40 instances) and not on random properties.
assert(
  importantLines.length < 40,
  `!important count is reasonable: ${importantLines.length} instances (< 40 allowed)`
);
// Verify none appear outside .rspku-settings-wrap context
const importantRules = css.split('}').filter(rule => rule.includes('!important'));
const outsideWrap = importantRules.filter(rule => /(^|,|\s)(body|html)\b|#wpcontent/.test(rule.split('{')[0] ?? ''));
assert(
  outsideWrap.length === 0,
  `!important not used on global selectors (no body/html/#wpcontent overrides)`
);

// ─── 7. Post Picker Grid ───
console.log('\n📋 Post Picker / Checkbox Picker');
assert(
  compactCss.includes('grid-template-columns:repeat(auto-fill,minmax(200px,1fr))'),
  'Picker grid auto-fills responsive columns'
);

// ─── 8. File Size Check ───
console.log('\n📋 File Size');
const sizeKB = (Buffer.byteLength(css, 'utf-8') / 1024).toFixed(1);
assert(parseFloat(sizeKB) < 30, `File size reasonable: ${sizeKB} KB (< 30 KB)`);
assert(parseFloat(sizeKB) > 5, `File not empty/truncated: ${sizeKB} KB (> 5 KB)`);

// ─── Summary ───
console.log(`\n${'═'.repeat(50)}`);
console.log(`  Results: ${passed} passed, ${failed} failed`);
console.log(`${'═'.repeat(50)}\n`);

process.exit(failed > 0 ? 1 : 0);
