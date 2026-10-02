<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$walletBalance = billing_balance($conn, $cid);
$unitBalances = billing_unit_balances($conn, $cid);

$my_services = $conn->query("SELECT * FROM client_services WHERE client_id = $cid ORDER BY created_at DESC");
$my_campaigns = $conn->query("SELECT COUNT(*) as c FROM sms_campaigns WHERE client_id = $cid")->fetch_assoc()['c'];
$my_tickets = $conn->query("SELECT COUNT(*) as c FROM support_tickets WHERE client_id = $cid")->fetch_assoc()['c'];
$open_tickets = $conn->query("SELECT COUNT(*) as c FROM support_tickets WHERE client_id = $cid AND status='open'")->fetch_assoc()['c'];
$active_services = $conn->query("SELECT COUNT(*) as c FROM client_services WHERE client_id = $cid AND status IN ('active','booked')")->fetch_assoc()['c'];

$messagingActive = billing_client_has_messaging($conn, $cid);
$portalLogin = billing_portal_login($conn, $cid);
$recent_tickets = $conn->query("SELECT * FROM support_tickets WHERE client_id = $cid ORDER BY created_at DESC LIMIT 3");
$recent_campaigns = $conn->query("SELECT * FROM sms_campaigns WHERE client_id = $cid ORDER BY created_at DESC LIMIT 3");
?>

<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Welcome, <?= e(get_client_name()) ?>!</h1>
    <p class="text-slate-500 text-sm">Wallet <?= e(billing_money_label($walletBalance)) ?> · <?= number_format($unitBalances['sms']) ?> SMS · <?= number_format((int) $unitBalances['voice_calls'] + (int) $unitBalances['voice_minutes']) ?> voice calls</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-brand-500/20"><svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg></div>
        <div class="dash-stat-value"><?= $active_services ?></div>
        <div class="dash-stat-label">Services</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-purple-500/20"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div class="dash-stat-value"><?= $my_campaigns ?></div>
        <div class="dash-stat-label">Messages</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-orange-500/20"><svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
        <div class="dash-stat-value"><?= $open_tickets ?></div>
        <div class="dash-stat-label">Open Tickets</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-blue-500/20"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
        <div class="dash-stat-value"><?= $my_tickets ?></div>
        <div class="dash-stat-label">Total Tickets</div>
    </div>
</div>

<?php if ($messagingActive): ?>
    <?php require __DIR__ . '/includes/sms-portal-card.php'; ?>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="shop.php" class="dash-action-card group">
        <div class="dash-action-icon bg-brand-500/20 group-hover:bg-brand-500/30"><svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Buy or book</p><p class="text-slate-500 text-xs">SMS, voice, domains, hosting, email, websites, training</p></div>
    </a>
    <a href="sms-portal.php" class="dash-action-card group">
        <div class="dash-action-icon bg-purple-500/20 group-hover:bg-purple-500/30"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div><p class="text-white font-medium text-sm">SMS portal</p><p class="text-slate-500 text-xs">Username and password for sending</p></div>
    </a>
    <a href="support.php" class="dash-action-card group">
        <div class="dash-action-icon bg-orange-500/20 group-hover:bg-orange-500/30"><svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Open Support Ticket</p><p class="text-slate-500 text-xs">Get help from our team</p></div>
    </a>
    <a href="profile.php" class="dash-action-card group">
        <div class="dash-action-icon bg-brand-500/20 group-hover:bg-brand-500/30"><svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Update Profile</p><p class="text-slate-500 text-xs">Manage your details</p></div>
    </a>
</div>

<!-- Recent Activity -->
<div class="grid lg:grid-cols-2 gap-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Recent Tickets</h3><a href="support.php" class="text-brand-400 text-sm hover:text-brand-300">View All →</a></div>
        <div class="divide-y divide-slate-800">
            <?php if ($recent_tickets && $recent_tickets->num_rows > 0): ?>
                <?php while ($t = $recent_tickets->fetch_assoc()): ?>
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div><p class="text-white text-sm font-medium"><?= e($t['subject']) ?></p><p class="text-slate-500 text-xs"><?= date('M d, Y', strtotime($t['created_at'])) ?></p></div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $t['status'] === 'open' ? 'bg-green-500/20 text-green-400' : ($t['status'] === 'resolved' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400') ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No tickets yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Recent Campaigns</h3><a href="campaigns.php" class="text-brand-400 text-sm hover:text-brand-300">View All →</a></div>
        <div class="divide-y divide-slate-800">
            <?php if ($recent_campaigns && $recent_campaigns->num_rows > 0): ?>
                <?php while ($c = $recent_campaigns->fetch_assoc()): ?>
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div><p class="text-white text-sm font-medium"><?= e($c['campaign_name']) ?></p><p class="text-slate-500 text-xs"><?= number_format($c['recipients_count']) ?> recipients</p></div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $c['status'] === 'sent' ? 'bg-green-500/20 text-green-400' : 'bg-slate-600/20 text-slate-400' ?>"><?= ucfirst($c['status']) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No campaigns yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
