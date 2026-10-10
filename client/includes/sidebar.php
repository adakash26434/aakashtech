<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$cid = get_client_id();
$navItems = [
    'index.php'     => ['Dashboard', 'layout-dashboard'],
    'shop.php'      => ['Buy Services', 'shopping-bag'],
    '../domain.php' => ['Domain registration', 'globe'],
    '../whois.php'  => ['WHOIS check up', 'search'],
    'domains.php'   => ['My domains', 'list-checks'],
    'services.php'  => ['My Services', 'server'],
    'wallet.php'    => ['Wallet', 'wallet'],
    'kyc.php'       => ['Identity', 'badge-check'],
    'sms-portal.php' => ['Send SMS', 'send'],
    'sms-logs.php' => ['SMS logs', 'scroll-text'],
    'sms-report.php' => ['Delivery report', 'chart-no-axes-column'],
    'sms-api.php' => ['SMS API', 'key-round'],
    'campaigns.php' => ['Messages', 'message-square-text'],
    'support.php'   => ['Support', 'life-buoy'],
    'manual.php'    => ['मार्गदर्शन', 'book-open'],
    'profile.php'   => ['Profile', 'user-round'],
];
$panelNav = hosting_client_panels($conn, (int) $cid);
$mailNav = mail_client_logins($conn, (int) $cid);
$siteNav = website_client_sites($conn, (int) $cid);
if ($panelNav || $mailNav || $siteNav) {
    $rebuilt = array();
    foreach ($navItems as $page => $item) {
        $rebuilt[$page] = $item;
        if ($page === 'services.php') {
            if ($panelNav) {
                $rebuilt['services.php#hosting-panel'] = array('cPanel', 'monitor');
            }
            if ($mailNav) {
                $rebuilt['services.php#domain-email'] = array('Email', 'mail');
            }
            if ($siteNav) {
                $rebuilt['services.php#website'] = array('Website', 'panels-top-left');
            }
        }
    }
    $navItems = $rebuilt;
}
$portalPage = isset($navItems[$currentPage]) ? $navItems[$currentPage][0] : 'Menu';
?>
<aside id="client-sidebar" class="fixed top-0 left-0 z-40 h-screen w-64 bg-dark-900 border-r border-dark-800 flex flex-col transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full invisible lg:visible'">
    <?php $identity = site_portal_identity($conn); ?>
    <div class="h-20 flex items-center gap-3 px-5 border-b border-dark-800">
        <?php if ($identity['logo'] !== ''): ?>
            <img src="<?= e($identity['logo']) ?>" alt="<?= e($identity['name']) ?>" class="portal-brand-logo">
        <?php else: ?>
            <div class="relative w-10 h-10 flex items-center justify-center">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-500 to-brand-700 rounded-xl rotate-45"></div>
                <span class="portal-logo-letter relative font-heading font-bold text-white text-base z-10"><?= e($identity['letter']) ?></span>
            </div>
            <div class="min-w-0 flex flex-col leading-tight">
                <span class="font-heading font-bold text-white text-sm truncate"><?= e($identity['name']) ?></span>
                <span class="text-xs text-slate-500">Client portal</span>
            </div>
        <?php endif; ?>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <?php foreach ($navItems as $page => $item): ?>
            <a href="<?= e($page) ?>" @click="sidebarOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $currentPage === $page ? 'bg-brand-500/15 text-brand-400 border border-brand-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' ?>">
                <i class="portal-icon" data-lucide="<?= e($item[1]) ?>" aria-hidden="true"></i>
                <?= $item[0] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="border-t border-dark-800 p-3">
        <div class="flex items-center gap-3 px-3 py-2 mb-2">
            <div class="w-9 h-9 rounded-lg bg-brand-500/20 flex items-center justify-center font-heading font-bold text-brand-400 text-sm"><?= strtoupper(substr(get_client_name(), 0, 1)) ?></div>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium truncate"><?= e(get_client_name()) ?></p>
                <p class="text-slate-500 text-xs truncate"><?= e($_SESSION['client_email'] ?? '') ?></p>
            </div>
        </div>
        <form method="POST" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 transition bg-transparent border-0 cursor-pointer text-left">
                <i class="portal-icon" data-lucide="log-out" aria-hidden="true"></i>
                Logout
            </button>
        </form>
    </div>
</aside>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <header class="portal-topbar h-20 border-b border-dark-800 bg-dark-900/50 backdrop-blur-xl flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
        <button @click="sidebarOpen = true" :aria-expanded="sidebarOpen" aria-controls="client-sidebar" aria-label="Open navigation" class="lg:hidden text-white p-2">
            <i class="portal-icon portal-menu-icon" data-lucide="menu" aria-hidden="true"></i>
        </button>
        <p class="lg:hidden min-w-0 truncate text-sm font-medium text-slate-400"><?= e($portalPage) ?></p>
        <div class="hidden lg:block"><span class="text-slate-500 text-sm"><?= e($identity['name']) ?> · Client</span></div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="manual.php" class="text-slate-400 hover:text-brand-400 text-sm flex items-center gap-2 transition" aria-label="मार्गदर्शन">
                <i class="portal-icon" data-lucide="book-open" aria-hidden="true"></i>
                <span class="hidden sm:inline">मार्गदर्शन</span>
            </a>
            <a href="../index.php" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-brand-400 text-sm flex items-center gap-2 transition">
                <i class="portal-icon" data-lucide="external-link" aria-hidden="true"></i>
                <span class="hidden sm:inline">View Site</span>
            </a>
        </div>
    </header>
    <main class="flex-1 p-4 lg:p-8">
        <?php if (function_exists('client_office_view') && client_office_view()): ?>
            <div class="mb-4 p-4 rounded-2xl border border-yellow-500/30 bg-yellow-500/10 text-yellow-100 text-sm flex flex-wrap items-center justify-between gap-3">
                <p>Office view of <?= e(get_client_name()) ?>. The client is not signed in here. Their password and authenticator were not used.</p>
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm rounded-xl">Back to admin</button>
                </form>
            </div>
        <?php endif; ?>
