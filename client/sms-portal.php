<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$notice = flash('billing');
$messagingActive = billing_client_has_messaging($conn, $cid);
$portalLogin = billing_portal_login($conn, $cid);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS portal</h1>
    <p class="text-slate-500 text-sm">The login for sending SMS after your credit is active.</p>
</div>

<?php if ($notice): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>

<?php if ($messagingActive): ?>
    <?php require __DIR__ . '/includes/sms-portal-card.php'; ?>
<?php else: ?>
    <div class="dash-panel">
        <div class="p-8">
            <p class="text-slate-300 text-sm mb-4">Buy SMS or voice credit first. The portal username and password appear on this page after that service is active.</p>
            <a href="shop.php?service=bulk-sms" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Buy SMS credit</a>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
