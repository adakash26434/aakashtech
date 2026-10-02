<?php
$navBase = (basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '') === 'index.php') ? '' : 'index.php';
if (!isset($siteName)) { $siteName = 'Aakash Technologies'; }
if (!isset($siteLogo)) { $siteLogo = ''; }
$brandLogo = site_logo_web_path($siteLogo);
$brandLetter = strtoupper(substr($siteName, 0, 1));
if ($brandLetter === '') { $brandLetter = 'A'; }
?>
<a class="skip-link" href="#main-content">Skip to content</a>

<header class="site-header" x-data="{ mobileOpen: false, scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 12"
        @keydown.escape.window="mobileOpen = false"
        :class="{ 'site-header--scrolled': scrolled }">
    <div class="site-nav wrap">
        <a class="brand" href="<?= $navBase === '' ? '#home' : 'index.php' ?>" aria-label="<?= site_escape($siteName) ?> home">
            <?php if ($brandLogo !== ''): ?>
                <img class="brand-logo" src="<?= site_escape($brandLogo) ?>" alt="<?= site_escape($siteName) ?>">
            <?php else: ?>
                <span class="brand-mark" aria-hidden="true"><?= site_escape($brandLetter) ?></span>
                <span class="brand-copy">
                    <span class="brand-name"><?= site_escape($siteName) ?></span>
                </span>
            <?php endif; ?>
        </a>

        <nav class="desktop-nav" aria-label="Main navigation">
            <a href="<?= $navBase === '' ? 'domain.php' : 'domain.php' ?>">Domain registration</a>
            <a href="<?= $navBase ?>#services">Services</a>
            <a href="<?= $navBase ?>#about">Why us</a>
            <a href="<?= $navBase ?>#process">How we work</a>
            <a href="<?= $navBase ?>#contact">Contact</a>
        </nav>

        <div class="nav-actions">
            <a class="portal-link" href="client/login.php">
                <i data-lucide="user-round" aria-hidden="true"></i>
                <span>Client portal</span>
            </a>
            <a class="button button--small button--primary nav-cta" href="<?= $navBase ?>#services">
                See services
                <i data-lucide="arrow-up-right" aria-hidden="true"></i>
            </a>
            <button class="menu-toggle" type="button" @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen" aria-controls="mobile-menu"
                    aria-label="Toggle navigation">
                <i x-show="!mobileOpen" data-lucide="menu" aria-hidden="true"></i>
                <i x-show="mobileOpen" x-cloak data-lucide="x" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <nav id="mobile-menu" class="mobile-nav wrap" x-show="mobileOpen" x-cloak
         x-transition:enter="mobile-nav-enter"
         x-transition:enter-start="mobile-nav-enter-start"
         x-transition:enter-end="mobile-nav-enter-end"
         @click.outside="mobileOpen = false" aria-label="Mobile navigation">
        <a href="domain.php" @click="mobileOpen = false">Domain registration</a>
        <a href="<?= $navBase ?>#services" @click="mobileOpen = false">Services</a>
        <a href="<?= $navBase ?>#about" @click="mobileOpen = false">Why us</a>
        <a href="<?= $navBase ?>#process" @click="mobileOpen = false">How we work</a>
        <a href="<?= $navBase ?>#contact" @click="mobileOpen = false">Contact</a>
        <a href="client/login.php">
            <i data-lucide="user-round" aria-hidden="true"></i>
            Client portal
        </a>
        <a class="button button--primary mobile-nav-cta" href="client/shop.php">Buy or book</a>
    </nav>
</header>