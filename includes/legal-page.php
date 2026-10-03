<?php
if (!isset($legalDoc) || !in_array($legalDoc, array('privacy', 'cookies', 'terms'), true)) {
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
$legalDocs = array(
    'privacy' => array('privacy_policy', 'Privacy policy', 'Privacy', 'privacy.php'),
    'cookies' => array('cookie_policy', 'Cookie notice', 'Cookies', 'cookies.php'),
    'terms' => array('terms_of_service', 'Terms of service', 'Terms', 'terms.php')
);
list($legalKey, $legalTitle, $legalKicker, $legalPath) = $legalDocs[$legalDoc];
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
    <?php
    require_once __DIR__ . '/seo.php';
    site_seo_print($legalTitle . ' | ' . $siteName, $legalTitle . ' for ' . $siteName . '.', $legalPath, array(), $siteLogo);
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <script defer src="https://unpkg.com/lucide@0.383.0"></script>
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
                <p class="legal-related">
                    <?php $legalLinks = array(); ?>
                    <?php foreach ($legalDocs as $otherDoc => $otherInfo): ?>
                        <?php if ($otherDoc !== $legalDoc) { $legalLinks[] = '<a href="' . site_escape($otherInfo[3]) . '">' . site_escape($otherInfo[1]) . '</a>'; } ?>
                    <?php endforeach; ?>
                    <?= implode(' · ', $legalLinks) ?>
                </p>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/site-footer.php'; ?>
</body>
</html>
