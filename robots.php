<?php
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /client/\n";
echo "Disallow: /cron/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /config.php\n";
echo "Disallow: /cpanel-config.php\n";
echo "\n";
echo 'Sitemap: ' . site_absolute_url('sitemap.xml') . "\n";
