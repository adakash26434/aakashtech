<?php
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/billing.php';

header('Content-Type: application/xml; charset=UTF-8');

$paths = array('/');
foreach (array_keys(billing_service_definitions()) as $slug) {
    $paths[] = 'service.php?slug=' . rawurlencode($slug);
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $path) {
    echo '  <url><loc>' . site_seo_escape(site_absolute_url($path)) . '</loc></url>' . "\n";
}
echo '</urlset>' . "\n";
