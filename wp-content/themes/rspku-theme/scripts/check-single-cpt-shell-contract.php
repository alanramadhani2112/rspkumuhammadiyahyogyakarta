<?php

declare(strict_types=1);

$theme = dirname(__DIR__);
$wpContent = dirname(dirname($theme));
$files = [
    'shell' => $theme . '/resources/views/partials/single-cpt-shell.twig',
    'rawat' => $theme . '/resources/views/pages/single-rawat-inap.twig',
    'poliklinik' => $theme . '/resources/views/pages/single-poliklinik.twig',
    'layanan' => $theme . '/resources/views/pages/single-layanan.twig',
    'management' => $theme . '/resources/views/pages/single-manajemen-rs.twig',
    'controller' => $theme . '/app/Controllers/TemplateController.php',
    'content_repository' => $theme . '/app/Repositories/ContentRepository.php',
    'theme_setup' => $theme . '/app/Setup/ThemeSetup.php',
    'cpt_plugin' => $wpContent . '/plugins/rspku-cpt/rspku-cpt.php',
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

function assert_not_contains(string $haystack, string $needle, string $message): void
{
    if (str_contains($haystack, $needle)) {
        fail($message);
    }

    pass($message);
}

function assert_same_int(int $actual, int $expected, string $message): void
{
    if ($actual !== $expected) {
        fail($message . " (expected {$expected}, got {$actual})");
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

foreach (['poliklinik', 'layanan'] as $template) {
    assert_contains($source[$template], "embed 'partials/single-cpt-shell.twig'", "{$template} uses shared shell");
    assert_contains($source[$template], "partials/content-not-found.twig", "{$template} keeps not-found behavior");
}

assert_contains($source['rawat'], "partials/content-not-found.twig", 'rawat keeps not-found behavior');

foreach (['partials/breadcrumb.twig', 'rspku-container space-y-10', 'block after_header', 'block before_content', 'rspku-prose max-w-none', 'block sidebar', 'block related'] as $needle) {
    assert_contains($source['shell'], $needle, "shell owns shared hierarchy: {$needle}");
}

foreach (['room_single.category', 'room_single.bed_count', 'room_single.size', 'room_single.rate', 'room_single.gallery', 'room_single.features', 'room_single.included', 'room_related'] as $needle) {
    assert_contains($source['rawat'], $needle, "rawat keeps data: {$needle}");
}

foreach (['room_single.category|default(\'-\', true)', 'room_single.bed_count|default(\'-\', true)', 'room_single.size|default(\'-\', true)'] as $needle) {
    assert_same_int(substr_count($source['rawat'], $needle), 2, "rawat renders placeholder twice: {$needle}");
}

assert_same_int(substr_count($source['rawat'], "value: room_single.rate ? 'Rp ' ~ room_single.rate : '-'"), 2, 'rawat rate uses exact dash fallback twice');

foreach (["{% if room_single.category %}", "{% if room_single.bed_count %}", "{% if room_single.size %}", "{% if room_single.rate %}", "value: 'Rp ' ~ room_single.rate"] as $needle) {
    assert_not_contains($source['rawat'], $needle, "rawat does not conditionally hide scalar slot: {$needle}");
}

foreach (['fallback', 'getRoomCategory', 'getRoomBedCount', 'getRoomSize', 'getRoomRate'] as $needle) {
    assert_not_contains($source['rawat'], $needle, "rawat template has no room fallback method reference: {$needle}");
}

foreach (['fallbackRoomDetails', 'hasRoomDetails'] as $needle) {
    assert_not_contains($source['content_repository'], 'function ' . $needle, "content repository has no {$needle} method");
    assert_not_contains($source['content_repository'], '->' . $needle . '(', "content repository has no {$needle} invocation");
    assert_not_contains($source['content_repository'], '::' . $needle . '(', "content repository has no static {$needle} invocation");
}

assert_contains($source['controller'], "settingArray('home_featured_services')", 'front page reads selected service IDs');
assert_contains($source['controller'], 'use WP_Post;', 'front page service normalizer imports WP_Post');
assert_matches($source['controller'], '/postsByIds\(\s*\$featuredServiceIds\s*,\s*\'layanan\'\s*,\s*fn\s*\([^)]*WP_Post\s+\$post/s', 'front page loads selected layanan posts by ID');
assert_contains($source['controller'], 'normalizeServicePublic($post)', 'front page normalizes selected services for public cards');
assert_contains($source['controller'], 'featuredServices(8)', 'front page falls back to eight featured services');
assert_not_contains($source['controller'], 'officialFeaturedServices', 'front page does not use officialFeaturedServices');

assert_contains($source['controller'], "is_singular('manajemen-rs')", 'management singular controller route exists');
assert_contains($source['management'], 'management_single', 'management singular template consumes context');
assert_contains($source['management'], "{% extends 'layouts/base.twig' %}", 'management singular template route exists');
assert_not_contains($source['theme_setup'], "is_singular('manajemen-rs')", 'management singular redirect absent');
assert_contains($source['cpt_plugin'], "add_filter('request', [self::class, 'resolveManagementSingle'], 1);", 'management detail route filter is registered');
assert_contains($source['cpt_plugin'], "'#^/manajemen-rs/([a-z0-9-]+)/?$#'", 'management detail route matches profile slugs');
assert_contains($source['cpt_plugin'], "\$queryVars['post_type'] = 'manajemen-rs';", 'management detail route targets CPT');
assert_contains($source['theme_setup'], '#^e-journal(/.*)?$#', 'e-journal redirect preserved');

foreach (['polyclinic_navigation', 'polyclinic.group', 'polyclinic_doctors', 'site: site', 'aria-current="page"', 'Lihat jadwal dokter'] as $needle) {
    assert_contains($source['poliklinik'], $needle, "poliklinik keeps feature: {$needle}");
}

foreach (['service_single.primary_category', 'service_doctors', 'service_related', 'site: site', 'Hubungi rumah sakit'] as $needle) {
    assert_contains($source['layanan'], $needle, "layanan keeps feature: {$needle}");
}
