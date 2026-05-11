<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists(\Rspku\Theme::class)) {
    \Rspku\Theme::render();
    return;
}

get_header();
?>
<main class="min-h-screen p-10">
    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
    <p>Theme bootstrap is not ready yet.</p>
</main>
<?php
get_footer();
