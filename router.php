<?php
$requestPath = rawurldecode((string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH));
$segments = explode('/', trim($requestPath, '/'));

foreach ($segments as $segment) {
    if ($segment !== '' && isset($segment[0]) && $segment[0] === '.') {
        http_response_code(404);
        exit;
    }
}

if ($requestPath === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}
if ($requestPath === '/robots.txt') {
    require __DIR__ . '/robots.php';
    return true;
}

$basename = basename($requestPath);
$blockedFiles = array(
    'config.php',
    'cpanel-config.php',
    'database.sql',
    'database.sqlite.sql',
    'README.md'
);

if (
    in_array($basename, $blockedFiles, true) ||
    preg_match('/\.sqlite(?:3)?$/i', $basename) ||
    strpos($requestPath, '/includes/') === 0 ||
    preg_match('#^/?uploads/.*\.(php|phtml|phar)$#i', $requestPath) ||
    preg_match('#^/?uploads/kyc/#i', $requestPath) ||
    preg_match('#^/?uploads/domains/#i', $requestPath)
) {
    http_response_code(404);
    exit;
}

return false;