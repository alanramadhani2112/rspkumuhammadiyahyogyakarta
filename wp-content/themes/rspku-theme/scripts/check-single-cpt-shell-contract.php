<?php

declare(strict_types=1);

$theme = dirname(__DIR__);
$files = [
    'shell' => $theme . '/resources/views/partials/single-cpt-shell.twig',
    'rawat' => $theme . '/resources/views/pages/single-rawat-inap.twig',
    'poliklinik' => $theme . '/resources/views/pages/single-poliklinik.twig',
    'layanan' => $theme . '/resources/views/pages/single-layanan.twig',
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

foreach (['rawat', 'poliklinik', 'layanan'] as $template) {
    assert_contains($source[$template], "embed 'partials/single-cpt-shell.twig'", "{$template} uses shared shell");
    assert_contains($source[$template], "partials/content-not-found.twig", "{$template} keeps not-found behavior");
}

foreach (['partials/breadcrumb.twig', 'rspku-container space-y-10', 'block after_header', 'block before_content', 'rspku-prose max-w-none', 'block sidebar', 'block related'] as $needle) {
    assert_contains($source['shell'], $needle, "shell owns shared hierarchy: {$needle}");
}

foreach (['room_single.category', 'room_single.bed_count', 'room_single.size', 'room_single.rate', 'room_single.gallery', 'room_single.features', 'room_single.included', 'room_related'] as $needle) {
    assert_contains($source['rawat'], $needle, "rawat keeps data: {$needle}");
}

foreach (['polyclinic_navigation', 'polyclinic.group', 'polyclinic_doctors', 'site: site', 'aria-current="page"', 'Lihat jadwal dokter'] as $needle) {
    assert_contains($source['poliklinik'], $needle, "poliklinik keeps feature: {$needle}");
}

foreach (['service_single.primary_category', 'service_doctors', 'service_related', 'site: site', 'Hubungi rumah sakit'] as $needle) {
    assert_contains($source['layanan'], $needle, "layanan keeps feature: {$needle}");
}
