<?php
$navBase = (basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '') === 'index.php') ? '' : 'index.php';
if (!isset($siteName)) { $siteName = 'Aakash Technologies'; }
if (!isset($siteEmail)) { $siteEmail = ''; }
if (!isset($siteWhatsapp)) { $siteWhatsapp = ''; }
$chatSettings = (isset($publicSite) && is_array($publicSite)) ? $publicSite : array(
    'whatsapp_number' => isset($siteWhatsapp) ? $siteWhatsapp : ''
);
$chatChannels = function_exists('site_chat_channels') ? site_chat_channels($chatSettings) : array();
$mailHref = function_exists('site_mail_href') ? site_mail_href($siteEmail, $siteName) : '';
if (!isset($siteLocation)) { $siteLocation = ''; }
if (!isset($siteTagline)) { $siteTagline = ''; }
if (!isset($siteFooter)) { $siteFooter = ''; }
if (!isset($siteLogo)) { $siteLogo = ''; }
if (!isset($siteSocials) || !is_array($siteSocials)) { $siteSocials = array(); }
$brandLogo = site_logo_web_path($siteLogo);
$brandLetter = strtoupper(substr($siteName, 0, 1));
if ($brandLetter === '') { $brandLetter = 'A'; }
$contactHref = ($navBase === '' ? '' : 'index.php') . '#contact';
?>
<footer class="site-footer">
    <div class="wrap">
        <div class="footer-main">
            <div class="footer-brand">
                <a class="brand brand--footer" href="<?= $navBase === '' ? '#home' : 'index.php' ?>" aria-label="<?= site_escape($siteName) ?> home">
                    <?php if ($brandLogo !== ''): ?>
                        <img class="brand-logo" src="<?= site_escape($brandLogo) ?>" alt="<?= site_escape($siteName) ?>">
                    <?php else: ?>
                        <span class="brand-mark" aria-hidden="true"><?= site_escape($brandLetter) ?></span>
                        <span class="brand-copy">
                            <span class="brand-name"><?= site_escape($siteName) ?></span>
                        </span>
                    <?php endif; ?>
                </a>
                <?php if ($siteTagline !== ''): ?><p><?= site_escape($siteTagline) ?></p><?php endif; ?>
                <?php if ($siteLocation !== ''): ?>
                <span class="footer-location">
                    <i data-lucide="map-pin" aria-hidden="true"></i>
                    <?= site_escape($siteLocation) ?>
                </span>
                <?php endif; ?>
                <?php if ($chatChannels || $siteSocials): ?>
                <div class="footer-social" aria-label="Social contact">
                    <?php foreach ($chatChannels as $channel): ?>
                        <a href="<?= site_escape($channel['href']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= site_escape($channel['label']) ?>"><?= $channel['icon'] ?></a>
                    <?php endforeach; ?>
                    <?php foreach ($siteSocials as $social): ?>
                        <a href="<?= site_escape($social['href']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= site_escape($social['label']) ?>"><?= $social['icon'] ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer-column">
                <h2>Explore</h2>
                <a href="domain.php">Domain registration</a>
                <a href="whois.php">WHOIS check up</a>
                <a href="<?= $navBase ?>#services">Services</a>
                <a href="<?= $navBase ?>#about">Why us</a>
                <a href="<?= $navBase ?>#process">How we work</a>
                <a href="<?= $navBase ?>#contact">Contact</a>
            </div>

            <div class="footer-column">
                <h2>Our services</h2>
                <a href="service.php?slug=bulk-sms">Bulk SMS</a>
                <a href="service.php?slug=bulk-voice">Bulk voice calls</a>
                <a href="service.php?slug=domain-registration">Domains</a>
                <a href="service.php?slug=hosting-server">Hosting &amp; servers</a>
                <a href="service.php?slug=professional-email">Domain email</a>
                <a href="service.php?slug=custom-websites">Websites</a>
                <a href="service.php?slug=cyber-security">Cyber training</a>
            </div>

            <div class="footer-column footer-contact">
                <h2>Start a conversation</h2>
                <?php if ($mailHref !== ''): ?>
                <a href="<?= site_escape($mailHref) ?>">
                    <i data-lucide="mail" aria-hidden="true"></i>
                    <?= site_escape($siteEmail) ?>
                </a>
                <?php endif; ?>
                <?php foreach ($chatChannels as $channel): ?>
                <a href="<?= site_escape($channel['href']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= $channel['icon'] ?>
                    <?= site_escape($channel['label']) ?>
                </a>
                <?php endforeach; ?>
                <a href="client/support.php">
                    <i data-lucide="ticket" aria-hidden="true"></i>
                    Support ticket
                </a>
                <a href="<?= site_escape($contactHref) ?>" class="footer-contact-link">
                    Tell us what you need
                    <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= site_escape($siteName) ?>. All rights reserved.</span>
            <?php if ($siteFooter !== ''): ?><span><?= site_escape($siteFooter) ?></span><?php endif; ?>
        </div>
    </div>
</footer>
<?php
$guestChats = array();
foreach ($chatChannels as $channel) {
    if ($channel['key'] === 'whatsapp' || $channel['key'] === 'messenger') {
        $guestChats[] = $channel;
    }
}
if ($guestChats) {
    $guestClass = 'guest-chat guest-chat--dock';
    include __DIR__ . '/site-guest-chat.php';
}
$aiReady = isset($conn) && $conn && function_exists('site_ai_ready') && site_ai_ready($conn);
if ($aiReady) {
    include __DIR__ . '/site-ask.php';
}
?>