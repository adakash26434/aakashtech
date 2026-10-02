<?php
$navBase = (basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '') === 'index.php') ? '' : 'index.php';
if (!isset($siteName)) { $siteName = 'Aakash Technologies'; }
if (!isset($siteEmail)) { $siteEmail = ''; }
if (!isset($sitePhone)) { $sitePhone = ''; }
if (!isset($siteWhatsapp)) { $siteWhatsapp = ''; }
$whatsappShown = $siteWhatsapp !== '' ? $siteWhatsapp : $sitePhone;
$whatsappHref = function_exists('site_whatsapp_href') ? site_whatsapp_href($whatsappShown) : '';
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
                <?php if ($whatsappHref !== '' || $siteSocials): ?>
                <div class="footer-social" aria-label="Social contact">
                    <?php if ($whatsappHref !== ''): ?>
                        <a href="<?= site_escape($whatsappHref) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l5.9-1.1A11 11 0 0 0 20.5 3.5zM12 20.3a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3.5.7.7-3.4-.2-.3A8.3 8.3 0 1 1 12 20.3zm4.6-6.2c-.3-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.6.1a6.8 6.8 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.2-.3a.5.5 0 0 0 0-.5c-.1-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4 4.2 4.2 0 0 0 3 .4 2.5 2.5 0 0 0 1.6-1.2 2 2 0 0 0 .1-1.2c-.1-.1-.3-.2-.6-.3z"/></svg></a>
                    <?php endif; ?>
                    <?php foreach ($siteSocials as $social): ?>
                        <a href="<?= site_escape($social['href']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= site_escape($social['label']) ?>"><?= $social['icon'] ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="footer-column">
                <h2>Explore</h2>
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
                <a href="service.php?slug=professional-email">Zoho email</a>
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
                <?php if ($whatsappHref !== ''): ?>
                <a href="<?= site_escape($whatsappHref) ?>" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l5.9-1.1A11 11 0 0 0 20.5 3.5zM12 20.3a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3.5.7.7-3.4-.2-.3A8.3 8.3 0 1 1 12 20.3zm4.6-6.2c-.3-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.6.1a6.8 6.8 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.2-.3a.5.5 0 0 0 0-.5c-.1-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4 4.2 4.2 0 0 0 3 .4 2.5 2.5 0 0 0 1.6-1.2 2 2 0 0 0 .1-1.2c-.1-.1-.3-.2-.6-.3z"/></svg>
                    WhatsApp <?= site_escape($whatsappShown) ?>
                </a>
                <?php endif; ?>
                <?php if ($sitePhone !== ''): ?>
                <a href="tel:<?= site_escape(preg_replace('/\s+/', '', $sitePhone)) ?>">
                    <i data-lucide="phone" aria-hidden="true"></i>
                    <?= site_escape($sitePhone) ?>
                </a>
                <?php endif; ?>
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