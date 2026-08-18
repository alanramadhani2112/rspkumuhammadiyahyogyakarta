# RSPKU Settings Admin UI Baseline

Timestamp: 2026-08-17T19:55:00+07:00

Scope: read only inventory for `wp-content/plugins/rspku-settings`.

Dirty worktree gate: FAIL.
See `00-clean-worktree.txt`.
No cleanup, app edit, git stage, commit, push, checkout, reset, stash,
or revert was done.

## Sources Read

- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-registry.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-admin.php`
- `wp-content/plugins/rspku-settings/includes/class-rspku-settings-api.php`
- `wp-content/plugins/rspku-settings/assets/admin.js`
- `wp-content/plugins/rspku-settings/rspku-settings.php`
- `.sisyphus/evidence/rspku-settings-admin-ui-refactor/00-clean-worktree.txt`
- `.sisyphus/notepads/rspku-settings-admin-ui-refactor/learnings.md`

## Count Method

Registry counted from `RSPKU_Settings_Registry::tabs()` only.

Helper script shape:

```php
$tabs = RSPKU_Settings_Registry::tabs();
$sections = 0;
$fields = 0;

foreach ($tabs as $key => $tab) {
    $tabSections = count($tab['sections'] ?? []);
    $tabFields = 0;

    foreach (($tab['sections'] ?? []) as $section) {
        $tabFields += count($section['fields'] ?? []);
    }

    $sections += $tabSections;
    $fields += $tabFields;
}
```

Result:

```text
umum: sections=1 fields=3
kontak: sections=4 fields=15
social: sections=1 fields=10
homepage: sections=8 fields=41
sejarah: sections=5 fields=25
gambar: sections=1 fields=5
branding: sections=2 fields=4
features: sections=2 fields=10
header: sections=2 fields=7
cta: sections=1 fields=2
footer: sections=2 fields=4
tools: sections=1 fields=1
TOTAL tabs=12 sections=30 fields=127
```

Expected count confirmed: 12 tabs, 30 sections, 127 fields.

Heavy tabs confirmed:

- Homepage: 41 fields.
- Sejarah: 25 fields.
- Kontak: 15 fields.

## Registry Inventory

### `umum`, 1 Section, 3 Fields

```text
identity: site_name, tagline, founded_year
```

### `kontak`, 4 Sections, 15 Fields

```text
phone: phone_igd, phone_igd_link, phone_main, phone_main_link
phone: whatsapp, whatsapp_link, email
address: address_street, address_district, address_city, address_province
hours: service_hours
maps: google_maps_embed_url, google_maps_link, google_maps_place_id
```

### `social`, 1 Section, 10 Fields

```text
social_accounts: social_instagram, social_instagram_handle
social_accounts: social_facebook, social_facebook_handle
social_accounts: social_youtube, social_youtube_handle
social_accounts: social_twitter, social_twitter_handle
social_accounts: social_linkedin, social_linkedin_handle
```

### `homepage`, 8 Sections, 41 Fields

```text
hero: hero_image_id, hero_eyebrow, hero_title, hero_description
hero: hero_cta_primary_text, hero_cta_primary_url
hero: hero_cta_secondary_text, hero_cta_secondary_url
homepage_cta: home_cta_primary_text, home_cta_primary_url
homepage_cta: home_cta_secondary_text, home_cta_secondary_url
promo_slider: promo_slide_1_enabled, promo_slide_1_image_id
promo_slider: promo_slide_1_title, promo_slide_1_description
promo_slider: promo_slide_1_cta_text, promo_slide_1_cta_url
promo_slider: promo_slide_2_enabled, promo_slide_2_image_id
promo_slider: promo_slide_2_title, promo_slide_2_description
promo_slider: promo_slide_2_cta_text, promo_slide_2_cta_url
promo_slider: promo_slide_3_enabled, promo_slide_3_image_id
promo_slider: promo_slide_3_title, promo_slide_3_description
promo_slider: promo_slide_3_cta_text, promo_slide_3_cta_url
metrics: metric_1_value, metric_1_label
metrics: metric_2_value, metric_2_label
metrics: metric_3_value, metric_3_label
home_sections: home_feature_image, home_cta_image
home_featured: home_featured_services
home_doctors: home_featured_doctors
home_reviews: home_featured_reviews
```

### `sejarah`, 5 Sections, 25 Fields

Each section has image ID, year, title, caption, and alt fields.

```text
history_hero: history_hero_image_id, history_hero_year
history_hero: history_hero_title, history_hero_caption, history_hero_alt
history_pioneers: history_pioneers_image_id, history_pioneers_year
history_pioneers: history_pioneers_title
history_pioneers: history_pioneers_caption, history_pioneers_alt
history_child_service: history_child_service_image_id
history_child_service: history_child_service_year
history_child_service: history_child_service_title
history_child_service: history_child_service_caption
history_child_service: history_child_service_alt
history_first_stone: history_first_stone_image_id
history_first_stone: history_first_stone_year, history_first_stone_title
history_first_stone: history_first_stone_caption, history_first_stone_alt
history_modernization: history_modernization_image_id
history_modernization: history_modernization_year
history_modernization: history_modernization_title
history_modernization: history_modernization_caption
history_modernization: history_modernization_alt
```

### `gambar`, 1 Section, 5 Fields

```text
page_images: image_dokter_archive, image_fasilitas, image_berita
page_images: image_poliklinik, image_layanan
```

### `branding`, 2 Sections, 4 Fields

```text
logos: logo_note
colors: brand_color_primary, brand_color_primary_dark, brand_color_accent
```

### `features`, 2 Sections, 10 Fields

```text
feature_toggles: feature_reading_progress, feature_toc
feature_toggles: feature_floating_share, feature_related_posts
feature_toggles: feature_popular_articles, feature_gtranslate
feature_toggles: feature_reviews_carousel, schema_enabled
language_switcher: language_switcher_label_id, language_switcher_label_en
```

### `header`, 2 Sections, 7 Fields

```text
header_behavior: header_logo_alt_id, header_sticky, header_topbar_enabled
header_behavior: header_emergency_enabled, header_emergency_label
header_cta: header_cta_text, header_cta_url
```

### `cta`, 1 Section, 2 Fields

```text
doctor_cta: doctor_appointment_cta_text
doctor_cta: doctor_appointment_fallback_url
```

### `footer`, 2 Sections, 4 Fields

```text
footer_content: footer_tagline, footer_copyright, footer_disclaimer
footer_links: footer_quick_links
```

### `tools`, 1 Section, 1 Field

```text
export_import: export_import_tool
```

## Admin Renderer Contract

- Menu slug: `rspku-settings`.
- Capability: `manage_options` through `RSPKU_Settings_Admin::CAPABILITY`.
- Renderer boundaries: `renderPage()`, `renderTabContent()`, `renderField()`.
- Settings form: `method="post"`, `action="admin-post.php"`.
- Settings form class: `rspku-settings-form`.
- Save nonce: `wp_nonce_field('rspku_settings_save', '_rspku_nonce')`.
- Save hidden action: `name="action" value="rspku_settings_save"`.
- Active tab hidden input: `name="active_tab"`.
- Save hook: `admin_post_rspku_settings_save` to `handleSave()`.
- Save gate: `current_user_can(self::CAPABILITY)`.
- Save nonce check: `check_admin_referer('rspku_settings_save', '_rspku_nonce')`.
- Save input source: `$_POST[RSPKU_SETTINGS_OPTION_KEY]`.
- Save sanitizer: `self::sanitize($input)`.
- Save write: `update_option(RSPKU_SETTINGS_OPTION_KEY, $clean)`.
- Save redirect preserves `page`, `tab`, and `settings-updated=true`.

## Import And Export Contract

- Export hook: `admin_post_rspku_settings_export` to `handleExport()`.
- Export form: `method="post"`, `action="admin-post.php"`.
- Export nonce: `wp_nonce_field('rspku_settings_export')`.
- Export hidden action: `name="action" value="rspku_settings_export"`.
- Export gate: `current_user_can(self::CAPABILITY)`.
- Export nonce check: `check_admin_referer('rspku_settings_export')`.
- Export payload keys: `exported_at`, `site_url`, `plugin_version`, `settings`.
- Export settings source: `get_option(RSPKU_SETTINGS_OPTION_KEY, [])`.
- Export response: JSON download named `rspku-settings-<Ymd-His>.json`.
- Import hook: `admin_post_rspku_settings_import` to `handleImport()`.
- Import form: `method="post"`, `action="admin-post.php"`.
- Import form uses `enctype="multipart/form-data"`.
- Import nonce: `wp_nonce_field('rspku_settings_import')`.
- Import hidden action: `name="action" value="rspku_settings_import"`.
- Import file input: `name="settings_file"`, accepts `application/json,.json`.
- Import gate: `current_user_can(self::CAPABILITY)`.
- Import nonce check: `check_admin_referer('rspku_settings_import')`.
- Import accepts wrapped export payload under `settings` or flat JSON payload.
- Import filters to default keys with `array_intersect_key($incoming, $defaults)`.
- Import sanitizer: `self::sanitize($filtered)`.
- Import write: `update_option(RSPKU_SETTINGS_OPTION_KEY, $clean)`.
- Import redirects to tools tab with `rspku_import` status.

## Option, API, Public Output Contract

- Option key constant: `RSPKU_SETTINGS_OPTION_KEY`.
- Option key value: `rspku_settings`.
- Option registration group: `rspku_settings_group`.
- Option type: `array`.
- Option default source: `RSPKU_Settings_Defaults::all()`.
- Public read helper: `RSPKU_Settings_API::get($key, $fallback)`.
- Public all helper: `RSPKU_Settings_API::all()`.
- `all()` merges defaults with saved option values.
- Timber context key: `rspku` from `addToTimberContext()`.
- Brand CSS output: `renderBrandColorsCSS()` on `wp_head` priority 5.
- Schema toggle bridge: `rspku/schema/enabled` filter.
- REST route: `GET /wp-json/rspku/v1/settings`.
- REST namespace: `rspku/v1`.
- REST route path: `/settings`.
- REST permission callback: `__return_true`.
- REST cache header: `Cache-Control: public, max-age=300`.
- REST public payload includes identity, contact, address, maps, and hours.
- REST public payload includes socials, hero, metrics, and image URLs.
- Cache flush hooks cover update and add for `rspku_settings`.
- Cache flush action: `rspku/settings/flushed`.

## Admin JavaScript Selectors

Media picker:

- Trigger: `.rspku-image-select` click.
- Container: `.rspku-image-upload` through `closest()`.
- Hidden value: `input[type="hidden"]` inside container.
- Preview wrapper: `.rspku-image-preview`.
- Preview image: `.rspku-image-preview-img`.
- Media frame uses `wp.media()`.
- Media frame title: `Pilih Gambar`.
- Media frame button text: `Gunakan Gambar Ini`.
- Media frame library type: `image`.

Remove image:

- Trigger: `.rspku-image-remove` click.
- Container: `.rspku-image-upload` through `closest()`.
- Clears hidden value to `0`.
- Hides `.rspku-image-preview`.
- Shows `.rspku-image-select`.

Repeater:

- Generic add trigger: `.rspku-repeater-add`.
- Link add trigger: `.rspku-repeater-add-link`.
- Review add trigger: `.rspku-repeater-add-review`.
- Container: `.rspku-repeater` through `closest()`.
- Row selector: `.rspku-repeater-row`.
- Remove trigger: `.rspku-repeater-remove`.
- New row name base: button `data-name`.
- Generic row fields: `label`, `time`, `highlight`.
- Link row fields: `label`, `url`.
- Review row fields: `name`, `rating`, `date_label`, `excerpt`.

Post picker:

- Search trigger: `.rspku-post-picker-search` input.
- Container: `.rspku-post-picker` through `closest()`.
- Dropdown: `.rspku-post-picker-dropdown`.
- Post type source: picker `data-post-type`.
- AJAX action: `rspku_search_posts`.
- AJAX nonce source: global `rspkuSettingsNonce`.
- Hidden value: `.rspku-post-picker-value`.
- Option selector: `.rspku-post-picker-option`.
- Option data: `data-id` and `data-title`.
- Selected wrapper: `.rspku-post-picker-selected`.
- Selected tag: `.rspku-post-picker-tag`.
- Remove trigger: `.rspku-post-picker-remove`.
- Outside click hides `.rspku-post-picker-dropdown`.

Checkbox picker:

- No JavaScript event handler found in `assets/admin.js`.
- PHP and CSS selectors include `.rspku-checkbox-picker`.
- PHP and CSS selectors include `.rspku-checkbox-picker-grid`.
- PHP and CSS selectors include `.rspku-checkbox-picker-item`.
- PHP and CSS selectors include `.rspku-checkbox-picker-label`.

Color picker:

- Init selector: `.rspku-color-picker`.

## Admin Browser And Export QA

Browser admin QA remains blocked.
No authenticated WordPress admin session was available in this task.
Prior inherited blocker said direct admin URL redirected to `/404/`.

Export flow was characterized from code only.
It is not marked as browser verified.
An authenticated admin session is required to submit the export form.
That session is also required to validate the JSON download through WordPress.

## Verification

Command:

```bash
cd wp-content/plugins/rspku-settings
npm test
```

Result: PASS.

```text
Results: 65 passed, 0 failed
```

No application PHP, CSS, or JS files were modified.
