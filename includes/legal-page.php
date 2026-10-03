<?php
if (!isset($legalDoc) || ($legalDoc !== 'privacy' && $legalDoc !== 'cookies')) {
    $legalDoc = 'privacy';
}
$publicSite = site_public_settings($conn);
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$legalKey = $legalDoc === 'cookies' ? 'cookie_policy' : 'privacy_policy';
$legalTitle = $legalDoc === 'cookies' ? 'Cookie notice' : 'Privacy policy';
$legalKicker = $legalDoc === 'cookies' ? 'Cookies' : 'Privacy';
$legalBody = site_legal_text($publicSite, $legalKey);
$legalParagraphs = preg_split("/\n{2,}/", $legalBody);
if (!is_array($legalParagraphs)) {
    $legalParagraphs = array($legalBody);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= site_escape($legalTitle) ?> | <?= site_escape($siteName) ?></title>
    <meta name="description" content="<?= site_escape($legalTitle) ?> for <?= site_escape($siteName) ?>.">
    <meta name="robots" content="index, follow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="assets/js/site.js"></script>
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/site-notice.php'; ?>
    <?php include __DIR__ . '/site-header.php'; ?>
    <main id="main-content">
        <section class="section detail-section">
            <div class="wrap legal-copy">
                <p class="section-kicker"><?= site_escape($legalKicker) ?></p>
                <h1 class="font-heading"><?= site_escape($legalTitle) ?></h1>
                <?php foreach ($legalParagraphs as $paragraph): ?>
                    <?php $paragraph = trim((string) $paragraph); ?>
                    <?php if ($paragraph !== ''): ?><p><?= nl2br(site_escape($paragraph)) ?></p><?php endif; ?>
                <?php endforeach; ?>
                <p class="legal-related"><?php if ($legalDoc === 'cookies'): ?><a href="privacy.php">Privacy policy</a><?php else: ?><a href="cookies.php">Cookie notice</a><?php endif; ?></p>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/site-footer.php'; ?>
</body>
</html>
