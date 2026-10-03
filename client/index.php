<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$walletBalance = 0;
$unitBalances = array('sms' => 0, 'voice_minutes' => 0, 'voice_calls' => 0);
$my_tickets = 0;
$open_tickets = 0;
$active_services = 0;
$messagingActive = false;
$recent_tickets = array();
$recent_sms = array();
$my_sms_sent = 0;
$panelAccounts = array();
$mailAccounts = array();
$websiteAccounts = array();
$trainingVisits = array();
$voiceWaiting = 0;
$hostingWaiting = 0;
$mailWaiting = 0;
$websiteWaiting = 0;
$trainingWaiting = 0;
$topupWaiting = 0;
$renewalWaiting = 0;
try {
    $walletBalance = billing_balance($conn, $cid);
    $unitBalances = billing_unit_balances($conn, $cid);
    $countStmt = $conn->prepare('SELECT COUNT(*) as c FROM support_tickets WHERE client_id = ?');
    $countStmt->bind_param('i', $cid);
    $countStmt->execute();
    $countRow = db_fetch_assoc($countStmt);
    $countStmt->close();
    $my_tickets = $countRow ? (int) $countRow['c'] : 0;
    $openStatus = 'open';
    $openStmt = $conn->prepare('SELECT COUNT(*) as c FROM support_tickets WHERE client_id = ? AND status = ?');
    $openStmt->bind_param('is', $cid, $openStatus);
    $openStmt->execute();
    $openRow = db_fetch_assoc($openStmt);
    $openStmt->close();
    $open_tickets = $openRow ? (int) $openRow['c'] : 0;
    $serviceStmt = $conn->prepare("SELECT COUNT(*) as c FROM client_services WHERE client_id = ? AND status IN ('active','booked')");
    $serviceStmt->bind_param('i', $cid);
    $serviceStmt->execute();
    $serviceRow = db_fetch_assoc($serviceStmt);
    $serviceStmt->close();
    $active_services = $serviceRow ? (int) $serviceRow['c'] : 0;
    $messagingActive = billing_client_has_messaging($conn, $cid);
    $recentStmt = $conn->prepare('SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC LIMIT 3');
    $recentStmt->bind_param('i', $cid);
    $recentStmt->execute();
    $recent_tickets = db_fetch_all($recentStmt);
    $recentStmt->close();
    $sentStatus = 'sent';
    $smsSentStmt = $conn->prepare('SELECT COUNT(*) as c FROM sms_messages WHERE client_id = ? AND status = ?');
    $smsSentStmt->bind_param('is', $cid, $sentStatus);
    $smsSentStmt->execute();
    $smsSentRow = db_fetch_assoc($smsSentStmt);
    $smsSentStmt->close();
    $my_sms_sent = $smsSentRow ? (int) $smsSentRow['c'] : 0;
    $recentSmsStmt = $conn->prepare('SELECT id, recipient, message_text, status, created_at FROM sms_messages WHERE client_id = ? ORDER BY created_at DESC, id DESC LIMIT 3');
    $recentSmsStmt->bind_param('i', $cid);
    $recentSmsStmt->execute();
    $recent_sms = db_fetch_all($recentSmsStmt);
    $recentSmsStmt->close();
    $panelAccounts = hosting_client_panels($conn, $cid);
    $mailAccounts = mail_client_logins($conn, $cid);
    $websiteAccounts = website_client_sites($conn, $cid);
    $trainingVisits = training_client_rows($conn, $cid);
    $voiceWaiting = voice_reserved_count($conn, $cid);
    $ownedStmt = $conn->prepare("SELECT * FROM client_services WHERE client_id = ? AND status IN ('active','booked','past_due')");
    $ownedStmt->bind_param('i', $cid);
    $ownedStmt->execute();
    foreach (db_fetch_all($ownedStmt) as $owned) {
        $ownedStatus = (string) $owned['status'];
        $ownedCode = (string) $owned['plan_code'];
        if ($ownedStatus === 'past_due') {
            $renewalWaiting++;
        }
        if (in_array($ownedCode, hosting_panel_plans(), true) && $ownedStatus === 'active' && !hosting_panel_ready($owned)) {
            $hostingWaiting++;
        }
        if (in_array($ownedCode, mail_login_plans(), true) && $ownedStatus === 'active' && !mail_login_ready($owned)) {
            $mailWaiting++;
        }
        if (in_array($ownedCode, website_plans(), true) && ($ownedStatus === 'booked' || $ownedStatus === 'active') && !website_ready($owned)) {
            $websiteWaiting++;
        }
        $visitMark = (string) $owned['panel_user'];
        if (in_array($ownedCode, training_plans(), true) && $ownedStatus === 'booked' && $visitMark !== 'confirmed' && $visitMark !== 'done') {
            $trainingWaiting++;
        }
    }
    $ownedStmt->close();
    $topupKind = 'topup';
    $topupStatus = 'pending';
    $topupStmt = $conn->prepare('SELECT COUNT(*) as c FROM wallet_entries WHERE client_id = ? AND kind = ? AND status = ?');
    $topupStmt->bind_param('iss', $cid, $topupKind, $topupStatus);
    $topupStmt->execute();
    $topupRow = db_fetch_assoc($topupStmt);
    $topupStmt->close();
    $topupWaiting = $topupRow ? (int) $topupRow['c'] : 0;
} catch (Throwable $exception) {
    error_log('Client dashboard could not be loaded.');
}
?>

<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Welcome, <?= e(get_client_name()) ?>!</h1>
    <p class="text-slate-500 text-sm">Wallet <?= e(billing_money_label($walletBalance)) ?> · <?= number_format((int) $unitBalances['sms']) ?> SMS · <?= number_format((int) $unitBalances['voice_calls']) ?> voice calls</p>
</div>
<?php
require_once __DIR__ . '/../includes/domain-check.php';
$unpaidDomains = 0;
$paidDomains = 0;
foreach (domain_client_requests($conn, $cid) as $domainRow) {
    if ($domainRow['status'] === 'requested' && isset($domainRow['price']) && (float) $domainRow['price'] > 0) {
        $unpaidDomains++;
    }
    if ($domainRow['status'] === 'paid') {
        $paidDomains++;
    }
}
$identity = billing_kyc_load($conn, $cid);
$identityStatus = (string) $identity['status'];
$showIdentity = $identityStatus !== 'approved' && ($identityStatus === 'pending' || $identityStatus === 'rejected' || billing_client_has_messaging($conn, $cid));
?>
<?php if ((int) $unitBalances['sms'] > 0 && (int) $unitBalances['sms'] < 100): ?>
    <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-200">
        <?= number_format((int) $unitBalances['sms']) ?> SMS credits left. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy more</a> before a larger send is refused.
    </div>
<?php endif; ?>
<?php if ($unpaidDomains > 0): ?>
    <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-100">
        A domain request is waiting for the yearly bill. <a href="domains.php" class="text-brand-300">Pay from the wallet</a>
    </div>
<?php endif; ?>
<?php if ($paidDomains > 0): ?>
    <div class="mb-6 p-4 bg-brand-500/10 border border-brand-500/30 rounded-xl text-sm text-brand-100">
        The domain bill is paid. The team registers the name, then it shows Active. <a href="domains.php" class="text-brand-300">See the request</a>
    </div>
<?php endif; ?>
<?php if ($hostingWaiting > 0 || $mailWaiting > 0 || $websiteWaiting > 0 || $trainingWaiting > 0): ?>
    <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-xl text-sm text-blue-300">
        <p>The team is finishing these. They show on <a href="services.php" class="text-brand-300">My Services</a> when ready.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <?php if ($hostingWaiting > 0): ?><li>cPanel login for hosting</li><?php endif; ?>
            <?php if ($mailWaiting > 0): ?><li>Email login for the mailbox plan</li><?php endif; ?>
            <?php if ($websiteWaiting > 0): ?><li>The published website link</li><?php endif; ?>
            <?php if ($trainingWaiting > 0): ?><li>The confirmed training date</li><?php endif; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if ($topupWaiting > 0): ?>
    <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-100">
        A wallet top-up is waiting for the team to confirm it. <a href="wallet.php" class="text-brand-300">See the wallet</a>
    </div>
<?php endif; ?>
<?php if ($renewalWaiting > 0): ?>
    <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-100">
        A renewal is waiting for wallet funds. It retries on its own. <a href="wallet.php" class="text-brand-300">Add funds</a>
    </div>
<?php endif; ?>
<?php if ($showIdentity): ?>
    <div class="mb-6 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-100">
        <?php if ($identityStatus === 'pending'): ?>
            KYC is with the team. More than 100 SMS stays closed until it is approved.
        <?php elseif ($identityStatus === 'rejected'): ?>
            KYC was sent back. Please update KYC before sending more than 100 SMS. <a href="kyc.php" class="text-brand-300">Update KYC</a>
        <?php else: ?>
            More than 100 SMS needs KYC. Please update KYC. <a href="kyc.php" class="text-brand-300">Update KYC</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="services.php" class="dash-stat-card">
        <div class="dash-stat-icon bg-brand-500/20"><svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg></div>
        <div class="dash-stat-value"><?= $active_services ?></div>
        <div class="dash-stat-label">Services</div>
    </a>
    <a href="sms-logs.php" class="dash-stat-card">
        <div class="dash-stat-icon bg-purple-500/20"><svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div class="dash-stat-value"><?= $my_sms_sent ?></div>
        <div class="dash-stat-label">SMS sent</div>
    </a>
    <a href="support.php" class="dash-stat-card">
        <div class="dash-stat-icon bg-orange-500/20"><svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg></div>
        <div class="dash-stat-value"><?= $open_tickets ?></div>
        <div class="dash-stat-label">Open Tickets</div>
    </a>
    <a href="support.php" class="dash-stat-card">
        <div class="dash-stat-icon bg-blue-500/20"><svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
        <div class="dash-stat-value"><?= $my_tickets ?></div>
        <div class="dash-stat-label">Total Tickets</div>
    </a>
</div>

<?php if ($messagingActive): ?>
    <?php require __DIR__ . '/includes/sms-portal-card.php'; ?>
<?php endif; ?>
<?php if ($panelAccounts): ?>
    <div class="dash-panel mb-8">
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-white text-base">cPanel</h2>
                <p class="text-slate-500 text-sm">Opens cPanel on the domain for this account.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($panelAccounts as $panel): ?>
                    <form method="POST" action="cpanel-open.php" target="_blank">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="service_id" value="<?= (int) $panel['id'] ?>">
                        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">cPanel login<?= hosting_panel_label($panel) !== '' ? ' · ' . e(hosting_panel_label($panel)) : '' ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($mailAccounts): ?>
    <div class="dash-panel mb-8">
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-white text-base">Email</h2>
                <p class="text-slate-500 text-sm">Opens the inbox at mail.the-domain after that name is pointed. Sign in with the mailbox address shown here and the password the team sent.</p>
                <?php
                $mailNames = array();
                foreach ($mailAccounts as $mailNameRow) {
                    foreach (mail_login_boxes($mailNameRow) as $mailAddress) {
                        $mailNames[] = $mailAddress;
                    }
                }
                ?>
                <?php if ($mailNames): ?><p class="text-slate-300 text-xs mt-1"><?= e(implode(' · ', $mailNames)) ?></p><?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($mailAccounts as $mail): ?>
                    <form method="POST" action="mail-open.php" target="_blank">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="service_id" value="<?= (int) $mail['id'] ?>">
                        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Open email<?= mail_login_domain($mail) !== '' ? ' · ' . e(mail_login_domain($mail)) : '' ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($websiteAccounts): ?>
    <div class="dash-panel mb-8">
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-heading font-semibold text-white text-base">Website</h2>
                <p class="text-slate-500 text-sm">Open a website published on this account.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($websiteAccounts as $site): ?>
                    <form method="POST" action="website-open.php" target="_blank">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="service_id" value="<?= (int) $site['id'] ?>">
                        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Open website<?= website_label($site) !== '' ? ' · ' . e(website_label($site)) : '' ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($trainingVisits || $voiceWaiting > 0): ?>
    <div class="dash-panel mb-8">
        <div class="p-5 space-y-2">
            <?php if ($voiceWaiting > 0): ?>
                <p class="text-slate-300 text-sm">Voice calls are waiting to be placed. The team places them, then the credits are used. <a href="campaigns.php" class="text-brand-300">See the jobs</a></p>
            <?php endif; ?>
            <?php foreach ($trainingVisits as $visit): ?>
                <p class="text-slate-300 text-sm"><?php if ((string) $visit['panel_user'] === 'done'): ?>Training visit complete, <?= e(date('M j, Y', strtotime(training_date($visit)))) ?>.<?php else: ?>Training visit confirmed for <?= e(date('M j, Y', strtotime(training_date($visit)))) ?>.<?php endif; ?></p>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <a href="shop.php" class="dash-action-card group">
        <div class="dash-action-icon bg-brand-500/20 group-hover:bg-brand-500/30"><svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Buy or book</p><p class="text-slate-500 text-xs">SMS, voice, domains, hosting, email, websites, training</p></div>
    </a>
    <?php if ($messagingActive && $identityStatus === 'approved' && (int) $unitBalances['sms'] === 0 && (int) $unitBalances['voice_calls'] > 0): ?>
    <a href="campaigns.php" class="dash-action-card group">
        <div class="dash-action-icon bg-purple-500/20 group-hover:bg-purple-500/30"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Voice jobs</p><p class="text-slate-500 text-xs">Save the script. The team places the call.</p></div>
    </a>
    <?php elseif ($messagingActive): ?>
    <a href="sms-portal.php" class="dash-action-card group">
        <div class="dash-action-icon bg-purple-500/20 group-hover:bg-purple-500/30"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Send SMS</p><p class="text-slate-500 text-xs"><?= $identityStatus === 'approved' ? 'Dashboard, logs, and API token' : 'Up to 100 SMS before KYC' ?></p></div>
    </a>
    <?php elseif ($messagingActive || $identityStatus === 'pending' || $identityStatus === 'rejected'): ?>
    <a href="kyc.php" class="dash-action-card group">
        <div class="dash-action-icon bg-purple-500/20 group-hover:bg-purple-500/30"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Identity</p><p class="text-slate-500 text-xs">Needed before SMS or a voice job</p></div>
    </a>
    <?php else: ?>
    <a href="shop.php?service=bulk-sms" class="dash-action-card group">
        <div class="dash-action-icon bg-purple-500/20 group-hover:bg-purple-500/30"><svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></div>
        <div><p class="text-white font-medium text-sm">Buy SMS credit</p><p class="text-slate-500 text-xs">Then send from this account</p></div>
    </a>
    <?php endif; ?>
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
            <?php if ($recent_tickets): ?>
                <?php foreach ($recent_tickets as $t): ?>
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div><p class="text-white text-sm font-medium"><?= e($t['subject']) ?></p><p class="text-slate-500 text-xs"><?= date('M d, Y', strtotime($t['created_at'])) ?></p></div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $t['status'] === 'open' ? 'bg-green-500/20 text-green-400' : ($t['status'] === 'resolved' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400') ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No tickets yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Recent SMS</h3><a href="sms-logs.php" class="text-brand-400 text-sm hover:text-brand-300">View All →</a></div>
        <div class="divide-y divide-slate-800">
            <?php if ($recent_sms): ?>
                <?php foreach ($recent_sms as $smsRow): ?>
                    <div class="p-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm font-medium"><?= e($smsRow['recipient']) ?></p>
                            <p class="text-slate-500 text-xs truncate"><?= e($smsRow['message_text']) ?></p>
                        </div>
                        <span class="text-slate-500 text-[10px] uppercase shrink-0"><?= e((string) $smsRow['status']) ?></span>
                        <?php if ((string) $smsRow['status'] === 'sent' || (string) $smsRow['status'] === 'failed'): ?>
                            <a href="sms-portal.php?reuse=<?= (int) $smsRow['id'] ?>" class="text-brand-400 text-xs shrink-0">Send again</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="p-8 text-center text-slate-500 text-sm">No SMS yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
