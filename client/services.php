<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = (int) get_client_id();
$notice = flash('billing');
$toggleMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_auto_renew'])) {
    verify_csrf();
    $serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';
    $toggleMessage = billing_set_auto_renew($conn, $cid, $serviceId, $enabled)
        ? ($enabled ? 'Auto-renew is on. This service continues as long as your wallet can cover it.' : 'Auto-renew is off. This service will not renew by itself.')
        : 'Auto-renew could not be changed for that service.';
}

$services = $conn->prepare('SELECT * FROM client_services WHERE client_id = ? ORDER BY id DESC');
$services->bind_param('i', $cid);
$services->execute();
$serviceRows = db_fetch_all($services);
$kycApproved = billing_kyc_approved($conn, $cid);
$services->close();
$units = billing_unit_balances($conn, $cid);
$balance = billing_balance($conn, $cid);
require_once __DIR__ . '/../includes/shop-view.php';
$definitions = billing_service_definitions();
$planService = array();
foreach (billing_load_plans($conn) as $planRow) {
    $planService[$planRow['code']] = $planRow['service_slug'];
}
$groupCounts = array('all' => count($serviceRows), 'active' => 0, 'waiting' => 0, 'attention' => 0, 'ended' => 0);
foreach ($serviceRows as $row) {
    $groupCounts[service_group((string) $row['status'])]++;
}
$show = isset($_GET['show']) && isset($groupCounts[$_GET['show']]) ? (string) $_GET['show'] : 'all';
$renewals = service_renewal_summary($serviceRows, $balance, 30);
$visibleRows = array();
foreach ($serviceRows as $row) {
    if ($show === 'all' || service_group((string) $row['status']) === $show) {
        $visibleRows[] = $row;
    }
}
?>
<div class="shop-head">
    <div>
        <h1 class="shop-title">My Services</h1>
        <p class="shop-sub">Everything you bought, what it is doing now, and when it renews.</p>
    </div>
    <a href="shop.php" class="shop-btn" style="margin-top:0">Buy a service</a>
</div>
<div class="svc-stats">
    <a href="wallet.php" class="svc-stat"><span>Wallet</span><strong><?= e(billing_money_label($balance)) ?></strong><em>Add funds</em></a>
    <a href="sms-portal.php" class="svc-stat"><span>SMS credits</span><strong><?= number_format($units['sms']) ?></strong><em>Send SMS</em></a>
    <a href="campaigns.php" class="svc-stat"><span>Voice calls</span><strong><?= number_format((int) $units['voice_calls']) ?></strong><em>Voice jobs</em></a>
    <div class="svc-stat"><span>Services</span><strong><?= (int) $groupCounts['active'] ?> active</strong><em><?= (int) $groupCounts['waiting'] ?> being set up</em></div>
</div>
<?php if ($notice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($toggleMessage !== ''): ?>
    <div class="mb-4 p-3 bg-brand-500/10 border border-brand-500/30 rounded-xl text-brand-400 text-sm"><?= e($toggleMessage) ?></div>
<?php endif; ?>

<?php if ($renewals['items']): ?>
    <section class="svc-renew<?= $renewals['short_by'] > 0 ? ' is-short' : '' ?>" aria-label="Coming renewals">
        <div>
            <h2><?= $renewals['short_by'] > 0 ? 'Add NPR ' . e(number_format($renewals['short_by'], 2)) . ' before your next renewal' : 'Renewals in the next ' . (int) $renewals['days'] . ' days are covered' ?></h2>
            <p><?= count($renewals['items']) ?> auto-renewal<?= count($renewals['items']) === 1 ? '' : 's' ?> add up to <?= e(billing_money_label($renewals['total'])) ?> (VAT included). Your wallet has <?= e(billing_money_label($balance)) ?>.</p>
        </div>
        <ul>
            <?php foreach (array_slice($renewals['items'], 0, 4) as $item): ?>
                <li><span><?= e($item['name']) ?></span><b><?= $item['days'] < 0 ? 'was due ' . e(date('j M', strtotime($item['date']))) : e(date('j M', strtotime($item['date']))) . ' · in ' . (int) $item['days'] . ' d' ?></b><i><?= e(billing_money_label($item['amount'])) ?></i></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($renewals['short_by'] > 0): ?><a class="co-alert-btn" href="wallet.php">Add funds</a><?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($serviceRows): ?>
    <nav class="shop-tabs" aria-label="Show">
        <?php foreach (array('all' => 'All', 'active' => 'Active', 'waiting' => 'Being set up', 'attention' => 'Needs attention', 'ended' => 'Ended') as $key => $label): ?>
            <?php if ($key !== 'all' && $groupCounts[$key] === 0) { continue; } ?>
            <a href="services.php?show=<?= e($key) ?>" class="<?= $show === $key ? 'is-on' : '' ?>"<?= $show === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?><span><?= (int) $groupCounts[$key] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <?php $panelAnchor = false; ?>
    <?php $mailAnchor = false; ?>
    <?php $siteAnchor = false; ?>
    <div class="svc-grid">
        <?php foreach ($visibleRows as $s): ?>
            <?php
            $renewable = isset($s['billing_cycle']) && ($s['billing_cycle'] === 'monthly' || $s['billing_cycle'] === 'yearly');
            $status = (string) $s['status'];
            $statusClass = $status === 'active' ? 'bg-green-500/20 text-green-400' : ($status === 'booked' ? 'bg-blue-500/20 text-blue-300' : ($status === 'past_due' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-red-500/20 text-red-400'));
            $brief = array();
            if (!empty($s['order_brief'])) {
                $decoded = json_decode((string) $s['order_brief'], true);
                if (is_array($decoded)) {
                    $brief = $decoded;
                }
            }
            $anchor = '';
            if (!$panelAnchor && in_array((string) $s['plan_code'], hosting_panel_plans(), true)) {
                $panelAnchor = true;
                $anchor = ' id="hosting-panel"';
            }
            if ($anchor === '' && !$mailAnchor && in_array((string) $s['plan_code'], mail_login_plans(), true)) {
                $mailAnchor = true;
                $anchor = ' id="domain-email"';
            }
            if ($anchor === '' && !$siteAnchor && in_array((string) $s['plan_code'], website_plans(), true)) {
                $siteAnchor = true;
                $anchor = ' id="website"';
            }
            ?>
            <?php
            $info = service_status_info($status, $renewable);
            $slug = isset($planService[$s['plan_code']]) ? $planService[$s['plan_code']] : '';
            $icon = $slug !== '' && isset($definitions[$slug]) ? $definitions[$slug]['icon'] : 'package';
            $daysLeft = !empty($s['next_renewal']) ? service_days_until($s['next_renewal']) : null;
            ?>
            <article class="svc-card is-<?= e($info['tone']) ?>"<?= $anchor ?>>
                <header class="svc-card-head">
                    <span class="shop-icon"><i data-lucide="<?= e($icon) ?>"></i></span>
                    <div><h2><?= e($s['service_name']) ?></h2><p><?= e($info['text']) ?></p></div>
                    <span class="svc-pill is-<?= e($info['tone']) ?>"><?= e($info['label']) ?></span>
                </header>
                <dl class="svc-facts">
                    <?php if (!empty($s['start_date'])): ?><div><dt>Started</dt><dd><?= e(date('j M Y', strtotime($s['start_date']))) ?></dd></div><?php endif; ?>
                    <?php if ($renewable && !empty($s['next_renewal'])): ?>
                        <div class="<?= $daysLeft !== null && $daysLeft <= 14 ? 'is-soon' : '' ?>"><dt><?= (int) $s['auto_renew'] === 1 ? 'Renews' : 'Ends' ?></dt><dd><?= e(date('j M Y', strtotime($s['next_renewal']))) ?><small><?= $daysLeft < 0 ? 'overdue' : ($daysLeft === 0 ? 'today' : 'in ' . (int) $daysLeft . ' days') ?></small></dd></div>
                    <?php endif; ?>
                    <?php if ((float) $s['price'] > 0): ?><div><dt>Bill</dt><dd><?= e(billing_money_label($s['price'])) ?><small><?= e(trim(billing_cycle_suffix($s['billing_cycle'] ?? ''), ' /')) ?> · VAT included</small></dd></div><?php endif; ?>
                    <?php if (!empty($s['detail_label'])): ?><div class="is-wide"><dt>Details</dt><dd><?= e($s['detail_label']) ?></dd></div><?php endif; ?>
                </dl>
                <?php if ($brief): ?>
                    <details class="svc-brief"><summary>Your order details</summary>
                        <dl>
                            <?php foreach ($brief as $label => $value): ?>
                                <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </details>
                <?php endif; ?>
                <div class="svc-actions">
                    <?php if (hosting_panel_ready($s)): ?>
                        <form method="POST" action="cpanel-open.php" target="_blank" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">cPanel login</button>
                        </form>
                        <p class="text-slate-500 text-xs mt-2">Opens cPanel<?= hosting_panel_label($s) !== '' ? ' for ' . e(hosting_panel_label($s)) : '' ?>.</p>
                    <?php elseif (in_array((string) $s['plan_code'], hosting_panel_plans(), true) && $status === 'active'): ?>
                        <p class="text-slate-400 text-xs mt-3">cPanel login appears here after the team finishes this hosting.</p>
                    <?php endif; ?>
                    <?php if (mail_login_ready($s)): ?>
                        <form method="POST" action="mail-open.php" target="_blank" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Open email<?= mail_login_domain($s) !== '' ? ' · ' . e(mail_login_domain($s)) : '' ?></button>
                        </form>
                        <p class="text-slate-500 text-xs mt-2">Opens the inbox<?= mail_login_domain($s) !== '' ? ' at mail.' . e(mail_login_domain($s)) : '' ?> after that name is pointed. Sign in with the password the team sent.</p>
                        <?php if (mail_login_boxes($s)): ?>
                            <p class="text-slate-300 text-xs mt-1"><?= e(implode(' · ', mail_login_boxes($s))) ?></p>
                        <?php endif; ?>
                    <?php elseif (in_array((string) $s['plan_code'], mail_login_plans(), true) && $status === 'active'): ?>
                        <p class="text-slate-400 text-xs mt-3">Email login appears here after the team finishes these mailboxes.</p>
                    <?php endif; ?>
                    <?php if (website_ready($s)): ?>
                        <form method="POST" action="website-open.php" target="_blank" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                            <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Open website<?= website_label($s) !== '' ? ' · ' . e(website_label($s)) : '' ?></button>
                        </form>
                        <p class="text-slate-500 text-xs mt-2">Opens the published website.</p>
                        <?php if (delivery_brief_value($s, 'Note') !== ''): ?><p class="text-slate-300 text-xs mt-1"><?= e(delivery_brief_value($s, 'Note')) ?></p><?php endif; ?>
                    <?php elseif (in_array((string) $s['plan_code'], website_plans(), true) && ($status === 'booked' || $status === 'active')): ?>
                        <p class="text-slate-400 text-xs mt-3">The website link appears here after the team publishes it.</p>
                    <?php endif; ?>
                    <?php if (in_array((string) $s['plan_code'], training_plans(), true) && (string) $s['panel_user'] === 'done' && training_date($s) !== ''): ?>
                        <p class="text-green-300 text-xs mt-3">This visit is complete. It was on <?= e(date('M j, Y', strtotime(training_date($s)))) ?><?= delivery_brief_value($s, 'Place') !== '' ? ' at ' . e(delivery_brief_value($s, 'Place')) : '' ?>.</p>
                    <?php elseif (in_array((string) $s['plan_code'], training_plans(), true) && (string) $s['panel_user'] === 'confirmed' && training_date($s) !== ''): ?>
                        <p class="text-brand-300 text-xs mt-3">Visit confirmed for <?= e(date('M j, Y', strtotime(training_date($s)))) ?><?= delivery_brief_value($s, 'Place') !== '' ? ' at ' . e(delivery_brief_value($s, 'Place')) : '' ?>.</p>
                    <?php elseif (in_array((string) $s['plan_code'], training_plans(), true) && $status === 'booked'): ?>
                        <p class="text-slate-400 text-xs mt-3">The visit date appears here after the team confirms it.</p>
                    <?php endif; ?>
                    <?php if ($status === 'booked' && !in_array((string) $s['plan_code'], website_plans(), true) && !in_array((string) $s['plan_code'], training_plans(), true)): ?>
                        <p class="text-blue-300 text-xs mt-3">Booked. The team builds or delivers this from the details above.</p>
                    <?php elseif (!empty($s['unit_kind']) && $s['unit_kind'] === 'sms'): ?>
                        <p class="text-slate-400 text-xs mt-3"><a href="sms-portal.php" class="text-brand-300">Send SMS from this account</a>. Credits fall when a message is sent.<?php if (!$kycApproved): ?> More than 100 SMS needs <a href="kyc.php" class="text-brand-300">identity</a>.<?php endif; ?></p>
                    <?php elseif (!empty($s['unit_kind']) && ($s['unit_kind'] === 'voice_calls' || $s['unit_kind'] === 'voice_minutes')): ?>
                        <p class="text-slate-400 text-xs mt-3"><?php if ($kycApproved): ?><a href="campaigns.php" class="text-brand-300">Save the voice job</a>. Credits are used when the team places the call.<?php else: ?><a href="kyc.php" class="text-brand-300">Submit identity</a> before a voice job can be saved.<?php endif; ?></p>
                    <?php endif; ?>
                    <?php if ($status === 'past_due'): ?>
                        <p class="text-yellow-300 text-xs mt-3">Renewal is waiting for wallet funds. It retries on its own.</p>
                    <?php elseif ($status === 'suspended' && $renewable): ?>
                        <p class="text-red-300 text-xs mt-3">Paused until the wallet can cover the renewal. No staff request is needed.</p>
                    <?php endif; ?>
                </div>
                    <?php if ($renewable): ?>
                        <form method="POST" class="svc-renew-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="enabled" value="<?= (int) $s['auto_renew'] === 1 ? '0' : '1' ?>">
                            <button type="submit" name="set_auto_renew" value="1" class="svc-switch<?= (int) $s['auto_renew'] === 1 ? ' is-on' : '' ?>" role="switch" aria-checked="<?= (int) $s['auto_renew'] === 1 ? 'true' : 'false' ?>"<?= (int) $s['auto_renew'] === 1 ? ' onclick="return confirm(\'Turn auto-renew off? This service stops when it expires unless you renew it yourself.\')"' : '' ?>>
                                <span class="svc-knob" aria-hidden="true"></span><span>Auto-renew is <?= (int) $s['auto_renew'] === 1 ? 'on' : 'off' ?>. <?= (int) $s['auto_renew'] === 1 ? 'Tap to turn it off.' : 'Tap to turn it on.' ?></span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="shop-empty"><h2>You have no services yet</h2><p>Buy SMS credits to send messages today, or a domain, hosting, email or website. Everything you buy appears here.</p><a href="shop.php" class="shop-btn">Browse services</a></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
