<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$total_inquiries = 0;
$new_inquiries = 0;
$total_clients = 0;
$active_services = 0;
$open_tickets = 0;
$total_campaigns = 0;
$pending_topups = 0;
$booked_orders = 0;
$recent_inquiries = false;
$recent_clients = false;
try {
    $total_inquiries = (int) $conn->query("SELECT COUNT(*) as c FROM inquiries")->fetch_assoc()['c'];
    $new_inquiries = (int) $conn->query("SELECT COUNT(*) as c FROM inquiries WHERE status='new'")->fetch_assoc()['c'];
    $total_clients = (int) $conn->query("SELECT COUNT(*) as c FROM client_users")->fetch_assoc()['c'];
    $active_services = (int) $conn->query("SELECT COUNT(*) as c FROM client_services WHERE status='active'")->fetch_assoc()['c'];
    $open_tickets = (int) $conn->query("SELECT COUNT(*) as c FROM support_tickets WHERE status='open'")->fetch_assoc()['c'];
    $total_campaigns = (int) $conn->query("SELECT COUNT(*) as c FROM sms_campaigns")->fetch_assoc()['c'];
    $pending_topups = (int) $conn->query("SELECT COUNT(*) as c FROM wallet_entries WHERE kind = 'topup' AND status = 'pending'")->fetch_assoc()['c'];
    $booked_orders = (int) $conn->query("SELECT COUNT(*) as c FROM client_services WHERE status = 'booked'")->fetch_assoc()['c'];
    $recent_inquiries = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
    $recent_clients = $conn->query("SELECT id, name, email, company, status, created_at FROM client_users ORDER BY created_at DESC LIMIT 5");
} catch (Throwable $exception) {
    error_log('Admin dashboard could not be loaded.');
}
?>

<!-- Page Content -->
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Dashboard</h1>
    <p class="text-slate-500 text-sm">Welcome back, <?= e(get_admin_name()) ?>! Here's what's happening.</p>
</div>

<?php if ((int) $pending_topups > 0): ?>
    <a href="billing.php" class="mb-6 block p-4 rounded-2xl border border-brand-500/30 bg-brand-500/10 text-brand-400 text-sm">
        <?= (int) $pending_topups ?> wallet top-up<?= (int) $pending_topups === 1 ? '' : 's' ?> waiting for confirmation. Renewals after that are automatic.
    </a>
<?php endif; ?>
<?php if ((int) $booked_orders > 0): ?>
    <a href="billing.php" class="mb-6 block p-4 rounded-2xl border border-blue-500/30 bg-blue-500/10 text-blue-300 text-sm">
        <?= (int) $booked_orders ?> website or training booking<?= (int) $booked_orders === 1 ? '' : 's' ?> waiting. The brief is on the billing page.
    </a>
<?php endif; ?>

<!-- Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-blue-500/20"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
        <div class="dash-stat-value"><?= $total_inquiries ?></div>
        <div class="dash-stat-label">Inquiries</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-green-500/20"><svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
        <div class="dash-stat-value"><?= $total_clients ?></div>
        <div class="dash-stat-label">Clients</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-brand-500/20"><svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg></div>
        <div class="dash-stat-value"><?= $active_services ?></div>
        <div class="dash-stat-label">Active Services</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-orange-500/20"><svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
        <div class="dash-stat-value"><?= $open_tickets ?></div>
        <div class="dash-stat-label">Open Tickets</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-purple-500/20"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div class="dash-stat-value"><?= $total_campaigns ?></div>
        <div class="dash-stat-label">Messages</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-icon bg-red-500/20"><svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div>
        <div class="dash-stat-value"><?= $new_inquiries ?></div>
        <div class="dash-stat-label">New Inquiries</div>
    </div>
</div>

<!-- Two columns -->
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Recent Inquiries -->
    <div class="dash-panel">
        <div class="dash-panel-header">
            <h3 class="font-heading font-semibold text-white">Recent Inquiries</h3>
            <a href="inquiries.php" class="text-brand-400 text-sm hover:text-brand-300">View All →</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($recent_inquiries && $recent_inquiries->num_rows > 0): ?>
                <?php while ($inq = $recent_inquiries->fetch_assoc()): ?>
                    <div class="p-4 hover:bg-slate-800/50 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-white font-medium text-sm"><?= e($inq['name']) ?></p>
                                <p class="text-slate-500 text-xs"><?= e($inq['email']) ?> · <?= e($inq['phone']) ?></p>
                                <p class="text-slate-400 text-xs mt-1"><?= e($inq['service'] ?: 'General') ?></p>
                            </div>
                            <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $inq['status'] === 'new' ? 'bg-green-500/20 text-green-400' : ($inq['status'] === 'read' ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-600/20 text-slate-400') ?>"><?= ucfirst($inq['status']) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No inquiries yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Clients -->
    <div class="dash-panel">
        <div class="dash-panel-header">
            <h3 class="font-heading font-semibold text-white">Recent Clients</h3>
            <a href="clients.php" class="text-brand-400 text-sm hover:text-brand-300">View All →</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php if ($recent_clients && $recent_clients->num_rows > 0): ?>
                <?php while ($cl = $recent_clients->fetch_assoc()): ?>
                    <div class="p-4 hover:bg-slate-800/50 transition flex items-center gap-3">
                        <div class="portal-avatar-letter w-9 h-9 rounded-lg flex items-center justify-center font-heading font-bold text-white text-sm" style="background: <?= e($cl['avatar_color'] ?? '#06b6d4') ?>"><?= strtoupper(substr($cl['name'], 0, 1)) ?></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-medium text-sm truncate"><?= e($cl['name']) ?></p>
                            <p class="text-slate-500 text-xs truncate"><?= e($cl['email']) ?></p>
                        </div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $cl['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= ucfirst($cl['status']) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No clients registered yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
