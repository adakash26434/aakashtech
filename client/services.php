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
$services->close();
$units = billing_unit_balances($conn, $cid);
$balance = billing_balance($conn, $cid);
?>
<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">My Services</h1>
        <p class="text-slate-500 text-sm">Wallet <?= e(billing_money_label($balance)) ?> · <?= number_format($units['sms']) ?> SMS · <?= number_format((int) $units['voice_calls'] + (int) $units['voice_minutes']) ?> voice calls</p>
    </div>
    <a href="shop.php" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Buy a service</a>
</div>

<?php if ($notice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($toggleMessage !== ''): ?>
    <div class="mb-4 p-3 bg-brand-500/10 border border-brand-500/30 rounded-xl text-brand-400 text-sm"><?= e($toggleMessage) ?></div>
<?php endif; ?>

<?php if ($serviceRows): ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <?php foreach ($serviceRows as $s): ?>
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
            ?>
            <article class="dash-panel">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <h2 class="font-heading font-semibold text-white text-base"><?= e($s['service_name']) ?></h2>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $statusClass ?>"><?= e(ucfirst(str_replace('_', ' ', $status))) ?></span>
                    </div>
                    <?php if ($brief): ?>
                        <dl class="text-xs space-y-1 mb-3">
                            <?php foreach ($brief as $label => $value): ?>
                                <div class="max-h-24 overflow-auto whitespace-pre-wrap"><dt class="text-slate-500 inline"><?= e($label) ?>: </dt><dd class="text-slate-300 inline"><?= e($value) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    <?php else: ?>
                        <p class="text-slate-500 text-xs mb-3"><?= e($s['description'] ?: 'No description') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($s['detail_label'])): ?>
                        <p class="text-slate-300 text-sm mb-3"><?= e($s['detail_label']) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        <?php if (!empty($s['start_date'])): ?><span>Started <?= e(date('M d, Y', strtotime($s['start_date']))) ?></span><?php endif; ?>
                        <?php if (!empty($s['next_renewal'])): ?><span>Next renewal <?= e(date('M d, Y', strtotime($s['next_renewal']))) ?></span><?php endif; ?>
                        <?php if ((float) $s['price'] > 0): ?><span><?= e(billing_money_label($s['price'])) ?><?= e(billing_cycle_suffix($s['billing_cycle'] ?? '')) ?></span><?php endif; ?>
                    </div>
                    <?php if ($status === 'booked'): ?>
                        <p class="text-blue-300 text-xs mt-3">Booked. The team builds or delivers this from the details above.</p>
                    <?php elseif (!empty($s['unit_kind']) && ($s['unit_kind'] === 'sms' || $s['unit_kind'] === 'voice_calls' || $s['unit_kind'] === 'voice_minutes')): ?>
                        <p class="text-slate-400 text-xs mt-3"><a href="sms-portal.php" class="text-brand-300">Open the SMS portal login</a>. Sending uses that username and password after the credit is active.</p>
                    <?php endif; ?>
                    <?php if ($status === 'past_due'): ?>
                        <p class="text-yellow-300 text-xs mt-3">Renewal is waiting for wallet funds. It retries on its own.</p>
                    <?php elseif ($status === 'suspended' && $renewable): ?>
                        <p class="text-red-300 text-xs mt-3">Paused until the wallet can cover the renewal. No staff request is needed.</p>
                    <?php endif; ?>
                    <?php if ($renewable): ?>
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="enabled" value="<?= (int) $s['auto_renew'] === 1 ? '0' : '1' ?>">
                            <button type="submit" name="set_auto_renew" value="1" class="text-brand-400 text-sm">
                                <?= (int) $s['auto_renew'] === 1 ? 'Turn auto-renew off' : 'Turn auto-renew on' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="dash-panel">
        <div class="p-12 text-center">
            <p class="text-slate-500 text-sm mb-4">You don't have any services yet.</p>
            <a href="shop.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Browse services</a>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
