<?php

declare(strict_types=1);

$theme = dirname(__DIR__);
$files = [
    'layout' => $theme . '/resources/views/layouts/base.twig',
    'css' => $theme . '/resources/css/app.css',
    'app' => $theme . '/resources/js/app.js',
    'themeSetup' => $theme . '/app/Setup/ThemeSetup.php',
    'search' => $theme . '/resources/views/pages/search.twig',
];

function fail(string $message): void
{
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function pass(string $message): void
{
    echo 'PASS: ' . $message . PHP_EOL;
}

function assert_contains(string $haystack, string $needle, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        fail($message);
    }

    pass($message);
}

function assert_count(string $haystack, string $needle, int $expected, string $message): void
{
    $count = substr_count($haystack, $needle);
    if ($count !== $expected) {
        fail("{$message}: expected {$expected}, got {$count}");
    }

    pass($message);
}

function assert_matches(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        fail($message);
    }

    pass($message);
}

function assert_class_tokens(string $haystack, string $pattern, array $tokens, string $message): void
{
    if (preg_match($pattern, $haystack, $matches) !== 1) {
        fail($message . ': element exists');
    }

    $classes = preg_split('/\s+/', trim($matches['class']));
    if (!is_array($classes)) {
        fail($message . ': classes parse');
    }

    foreach ($tokens as $token) {
        if (!in_array($token, $classes, true)) {
            fail("{$message}: missing {$token}");
        }
    }

    pass($message);
}

$source = [];
foreach ($files as $key => $path) {
    if (!is_file($path)) {
        fail("required source file exists: {$path}");
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        fail("required source file is readable: {$path}");
    }

    $source[$key] = $contents;
}
pass('required source files readable');

foreach (['x-data="siteSearch"', '@keydown.escape.window="closeSearch()"', 'method="get" action="{{ site.url }}"', 'type="search" name="s"', 'x-ref="query"'] as $needle) {
    assert_contains($source['layout'], $needle, "global search form contract: {$needle}");
}

assert_class_tokens(
    $source['layout'],
    '#<img\b(?=[^>]*alt="\{\{ site_logo\.alt\|default\(site\.name\) \}\}")[^>]*class="(?<class>[^"]*)"#',
    ['h-auto', 'max-h-10', 'max-w-[10rem]', 'w-auto', 'object-contain', 'sm:h-14', 'sm:max-h-none', 'sm:max-w-none'],
    'mobile header logo keeps constrained responsive sizing'
);

foreach (['@click="openSearch($event)"', ':aria-expanded="open.toString()"', 'aria-controls="site-search-panel"', 'aria-label="Buka pencarian situs"'] as $needle) {
    assert_count($source['layout'], $needle, 2, "desktop and mobile search triggers keep accessibility: {$needle}");
}

assert_matches(
    $source['layout'],
    '#<button\b(?=[^>]*@click="openSearch\(\$event\)")(?=[^>]*aria-label="Buka pencarian situs")(?=[^>]*class="[^"]*\bhidden\b)(?=[^>]*class="[^"]*\bsm:inline-flex\b)[^>]*>#',
    'desktop search trigger starts at sm breakpoint'
);
assert_matches(
    $source['layout'],
    '#<button\b(?=[^>]*@click="openSearch\(\$event\)")(?=[^>]*aria-label="Buka pencarian situs")(?=[^>]*class="[^"]*\bsm:hidden\b)[^>]*>#',
    'mobile search trigger only applies below sm'
);

foreach (['id="site-search-panel"', ':role="isDesktop() ? null : \'dialog\'"', ':aria-modal="isDesktop() ? null : \'true\'"', 'aria-labelledby="site-search-title"', 'id="site-search-title"', '@click="closeSearch()"', 'aria-label="Tutup pencarian"'] as $needle) {
    assert_contains($source['layout'], $needle, "responsive search panel accessibility contract: {$needle}");
}

foreach (['sm:inset-auto', 'sm:w-[26rem]', ':style="popoverStyle()"', '@click.outside="isDesktop() && closeSearch()"'] as $needle) {
    assert_contains($source['layout'], $needle, "desktop disclosure popover contract: {$needle}");
}

foreach (['fixed inset-x-4 top-4', 'bg-slate-950/50 backdrop-blur-sm', '@click="closeSearch()"'] as $needle) {
    assert_contains($source['layout'], $needle, "mobile modal top sheet contract: {$needle}");
}

assert_contains($source['layout'], 'Tekan Enter untuk mencari · Esc untuk menutup', 'native search form presentation contract: keyboard hint copy');
assert_class_tokens(
    $source['layout'],
    '#icon\(\'search\', \{ class: \'(?<class>[^\']*)\' \}\)\s*\}\}\s*<input\b[^>]*id="site-search-query"#',
    ['pointer-events-none', 'absolute', 'left-3', 'top-1/2', 'h-4', 'w-4', '-translate-y-1/2', 'text-slate-400'],
    'native search form presentation contract: internal search icon positioning'
);
assert_class_tokens(
    $source['layout'],
    '#<input\b(?=[^>]*id="site-search-query")[^>]*class="(?<class>[^"]*)"#',
    ['rspku-input', 'rspku-input-with-leading-icon'],
    'native search form presentation contract: search input pairs base and leading icon modifier'
);
assert_matches(
    $source['css'],
    '#\.rspku-input-with-leading-icon\s*\{\s*padding-left:\s*2\.5rem;\s*\}#',
    'native search form presentation contract: leading icon modifier offsets input text'
);
assert_class_tokens(
    $source['layout'],
    '#<button\b(?=[^>]*type="submit")[^>]*class="(?<class>[^"]*)"#',
    ['rspku-button', 'rspku-button-primary', 'rspku-button-sm'],
    'native search form presentation contract: primary compact submit'
);

foreach (['Alpine.data(\'siteSearch\'', 'opener: null', 'openSearch(event)', 'closeSearch()', 'isDesktop()', "window.matchMedia('(min-width: 640px)').matches", 'popoverStyle()', 'getBoundingClientRect()'] as $needle) {
    assert_contains($source['app'], $needle, "siteSearch Alpine behavior: {$needle}");
}

foreach (['@keydown.tab="trapFocus($event)"', 'trapFocus(event)', '!this.open || this.isDesktop()', 'event.currentTarget.querySelectorAll(this.focusableSelector)', 'event.shiftKey && document.activeElement === first', '!event.shiftKey && document.activeElement === last'] as $needle) {
    assert_contains($source['app'] . $source['layout'], $needle, "siteSearch mobile focus trap contract: {$needle}");
}

foreach (['if (!this.open)', 'const opener = this.opener', 'this.opener = null', 'opener?.focus?.()'] as $needle) {
    assert_contains($source['app'], $needle, "siteSearch close guard and focus restoration contract: {$needle}");
}

assert_contains($source['themeSetup'], "add_action('pre_get_posts', [self::class, 'configureSearchPostTypes']);", 'pre_get_posts wires global search type allowlist');
assert_contains($source['themeSetup'], 'public static function configureSearchPostTypes(\WP_Query $query): void', 'search post type configurator exists');
assert_contains($source['themeSetup'], 'if (is_admin() || !$query->is_main_query() || !$query->is_search())', 'search post type configurator only touches frontend main search');
assert_matches(
    $source['themeSetup'],
    '#\$query->set\(\s*\'post_type\',\s*\[\s*\'post\',\s*\'page\',\s*\'dokter\',\s*\'poliklinik\',\s*\'layanan\',\s*\'jurnal\',\s*\'manajemen-rs\',\s*\'rawat-inap\',\s*\]\s*\);#',
    'global search exact eight post types stay allowlisted in order'
);

foreach (["search_view.search_action|default(site.url)", 'type="search" name="s"', 'pagination_meta.total|default(0)', 'pagination_meta.per_page|default(10)', 'pagination_meta.current_page|default(1)', 'pagination_meta.total_pages|default(1)', "item_label: pagination_meta.item_label|default('hasil')"] as $needle) {
    assert_contains($source['search'], $needle, "search results page contract: {$needle}");
}

foreach (["{% set type_label = {", "post: 'Berita'", "page: 'Halaman'", "dokter: 'Dokter'", "poliklinik: 'Poliklinik'", "layanan: 'Layanan'", "jurnal: 'E-Jurnal'", "'manajemen-rs': 'Manajemen RS'", "'rawat-inap': 'Rawat inap'", "}[item.post_type]|default('Hasil')", 'badge: type_label'] as $needle) {
    assert_contains($source['search'], $needle, "search result type label contract: {$needle}");
}
