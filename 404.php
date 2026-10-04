<?php
http_response_code(404);
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/billing.php';
require_once __DIR__ . '/includes/seo.php';

$publicSite = site_public_defaults();
$conn = null;
try {
    require_once __DIR__ . '/config.php';
    $publicSite = site_public_settings($conn);
} catch (Throwable $exception) {
    error_log('Not-found page is showing default site details.');
}
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];

$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
$siteBase = '/';
if ($docRoot !== false && strpos(__DIR__, $docRoot) === 0) {
    $siteBase = rtrim(str_replace('\\', '/', substr(__DIR__, strlen($docRoot))), '/') . '/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= site_escape($siteBase) ?>">
    <title>Page not found | <?= site_escape($siteName) ?></title>
    <meta name="robots" content="noindex, follow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <link rel="stylesheet" href="assets/css/ui-shared.css">
    <script defer src="assets/vendor/lucide-0.383.0.min.js"></script>
    <script defer src="assets/vendor/alpine-3.14.9.min.js"></script>
    <script defer src="assets/js/site.js"></script>
    <script defer src="assets/js/forms.js"></script>
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/includes/site-header.php'; ?>
    <main id="main-content">
        <section class="section detail-section">
            <div class="wrap legal-copy">
                <p class="section-kicker">404</p>
                <h1 class="font-heading">This page is not here</h1>
                <p>The link may be old, or the address has a typo. These pages are a good place to start.</p>
                <p class="legal-related">
                    <a href="index.php">Home</a> ·
                    <a href="index.php#services">Services</a> ·
                    <a href="domain.php">Domain registration</a> ·
                    <a href="index.php#contact">Contact</a> ·
                    <a href="client/login.php">Client login</a>
                </p>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
