<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$theme = dirname(__DIR__);

$files = [
    'defaults' => $root . '/plugins/rspku-settings/includes/class-rspku-settings-defaults.php',
    'admin' => $root . '/plugins/rspku-settings/includes/class-rspku-settings-admin.php',
    'api' => $root . '/plugins/rspku-settings/includes/class-rspku-settings-api.php',
    'controller' => $theme . '/app/Controllers/TemplateController.php',
    'twig' => $theme . '/resources/views/pages/page-sejarah-kami.twig',
];

$source = [];

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

function assert_regex(string $source, string $pattern, string $message): void
{
    if (preg_match($pattern, $source) !== 1) {
        fail($message);
    }

    pass($message);
}

function assert_not_regex(string $source, string $pattern, string $message): void
{
    if (preg_match($pattern, $source) === 1) {
        fail($message);
    }

    pass($message);
}

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

$slots = [
    'history_hero',
    'history_pioneers',
    'history_child_service',
    'history_first_stone',
    'history_modernization',
];

$defaultSuffixes = [
    'image_id' => '0',
    'year' => "''",
    'title' => "''",
    'caption' => "''",
    'alt' => "''",
];

foreach ($slots as $slot) {
    foreach ($defaultSuffixes as $suffix => $value) {
        assert_contains($source['defaults'], "'{$slot}_{$suffix}' => {$value}", "default exists: {$slot}_{$suffix}");
    }
}

foreach ($slots as $slot) {
    assert_contains($source['admin'], "'{$slot}_image_id'", "admin image key exists: {$slot}_image_id");
    assert_contains($source['admin'], "'{$slot}_caption'", "admin history caption key exists: {$slot}_caption");
}

assert_regex($source['admin'], '~private\s+static\s+function\s+isHistoryCaptionField\s*\([^)]*\)\s*:\s*bool~', 'admin defines isHistoryCaptionField()');
assert_regex($source['admin'], '~public\s+static\s+function\s+imageKeys\s*\([^)]*\)\s*:\s*array~', 'admin defines imageKeys()');
assert_regex($source['admin'], '~private\s+static\s+function\s+sanitizeImageId\s*\([^)]*\)\s*:\s*int~', 'admin defines sanitizeImageId()');
assert_contains($source['admin'], 'sanitize_textarea_field((string) $value)', 'history captions use sanitize_textarea_field');
assert_contains($source['admin'], 'wp_attachment_is_image($id)', 'image ID sanitizer validates attachments as images');
assert_contains($source['admin'], '? $id : 0', 'invalid image ID falls back to 0');

assert_contains($source['controller'], 'private static function historyPageContext(): array', 'controller defines historyPageContext()');
assert_contains($source['controller'], 'private static function historyGalleryContext(): array', 'controller defines historyGalleryContext()');
assert_regex(
    $source['controller'],
    '~\$slots\s*=\s*\[\s*\'history_hero\'\s*,\s*\'history_pioneers\'\s*,\s*\'history_child_service\'\s*,\s*\'history_first_stone\'\s*,\s*\'history_modernization\'\s*,?\s*\]~s',
    'controller gallery slot order is exact'
);
assert_contains($source['controller'], '$imageId < 1', 'controller skips missing image IDs');
assert_contains($source['controller'], '!wp_attachment_is_image($imageId)', 'controller skips invalid image IDs');
foreach (['year', 'title', 'caption', 'alt'] as $field) {
    assert_contains($source['controller'], "\${$field} === ''", "controller skips empty {$field}");
}

if (preg_match('/history_/', $source['api']) === 1) {
    fail('REST public API payload has no history_ keys');
}
pass('REST public API payload has no history_ keys');

assert_contains($source['twig'], 'history_page.gallery|default([])', 'Twig reads history_page.gallery|default([])');
foreach ($slots as $slot) {
    assert_contains($source['twig'], "history_gallery_slots.{$slot}|default(null)", "Twig maps slot variable: {$slot}");
}

$responsiveIncludes = substr_count($source['twig'], "include 'partials/responsive-image.twig'");
if ($responsiveIncludes !== 5) {
    fail("Twig includes responsive image partial five times, found {$responsiveIncludes}");
}
pass('Twig includes responsive image partial five times');

assert_regex($source['twig'], '~<figure\b~', 'Twig renders <figure>');
assert_regex($source['twig'], '~<figcaption\b~', 'Twig renders <figcaption>');
assert_regex($source['twig'], '~history_hero\.image_id.*?eager:\s*true~s', 'Twig hero image include is eager');
assert_not_regex($source['twig'], '~(?:sejarah|history)[^\'"\s>]*\.(?:png|jpe?g|webp|avif)~i', 'Twig has no hardcoded local history image paths');

pass('history gallery contract');
