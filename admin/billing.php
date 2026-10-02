<?php
require_once __DIR__ . '/../config.php';

require_admin();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['approve_topup'])) {
        $msg = billing_approve_topup($conn, (int) $_POST['entry_id'])
            ? 'Top-up added to the client wallet.'
            : '';
        $err = $msg === '' ? 'That top-up could not be confirmed.' : '';
    } elseif (isset($_POST['reject_topup'])) {
        $msg = billing_reject_topup($conn, (int) $_POST['entry_id']) ? 'Top-up rejected.' : '';
        $err = $msg === '' ? 'That top-up could not be rejected.' : '';
    } elseif (isset($_POST['save_slabs']) && isset($_POST['slab']) && is_array($_POST['slab'])) {
        $slabError = billing_save_slabs($conn, $_POST['slab']);
        $msg = $slabError === '' ? 'SMS and voice rates updated. The public pages and checkout use these slabs.' : '';
        $err = $slabError;
    } elseif (isset($_POST['save_prices']) && isset($_POST['price']) && is_array($_POST['price'])) {
        $saved = 0;
        foreach ($_POST['price'] as $code => $value) {
            if (billing_update_price($conn, (string) $code, (string) $value)) {
                $saved++;
            }
        }
        $msg = $saved > 0 ? 'Plan prices updated. New purchases use these amounts. Existing renewals keep the price from when they were bought.' : '';
        $err = $saved > 0 ? '' : 'Enter a valid price for each plan.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$pending = $conn->query("SELECT w.*, c.name, c.email FROM wallet_entries w JOIN client_users c ON c.id = w.client_id WHERE w.kind = 'topup' AND w.status = 'pending' ORDER BY w.id ASC");
$plans = billing_load_plans($conn);
$smsSlabs = billing_load_slabs($conn, 'bulk-sms');
$voiceSlabs = billing_load_slabs($conn, 'bulk-voice');
$subscriptions = $conn->query("SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.plan_code IS NOT NULL AND s.plan_code != '' ORDER BY s.id DESC LIMIT 30");
$renewals = $conn->query('SELECT * FROM renewal_events ORDER BY id DESC LIMIT 12');
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Billing</h1>
    <p class="text-slate-500 text-sm">Confirm incoming wallet funds. Renewals after that run without a staff action.</p>
</div>

<?php if ($msg !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">Wallet top-ups waiting for confirmation</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-800 text-left">
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Client</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Amount</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Method</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Reference</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($pending && $pending->num_rows > 0): ?>
                    <?php while ($row = $pending->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-3"><p class="text-white text-sm"><?= e($row['name']) ?></p><p class="text-slate-500 text-xs"><?= e($row['email']) ?></p></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e(billing_money_label($row['amount'])) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(ucfirst($row['method'])) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= e($row['reference_note']) ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="entry_id" value="<?= (int) $row['id'] ?>">
                                    <button type="submit" name="approve_topup" value="1" class="text-brand-400 text-sm">Confirm</button>
                                    <button type="submit" name="reject_topup" value="1" class="text-red-400 text-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500 text-sm">No top-ups waiting.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="dash-panel mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">SMS and voice volume rates</h3>
        <p class="text-slate-500 text-xs mt-1">A quantity inside a row uses that row’s rate. Raise the rate for smaller sends and lower it for larger ones.</p>
    </div>
    <form method="POST" class="p-5 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach (array('Bulk SMS' => $smsSlabs, 'Auto voice calls' => $voiceSlabs) as $heading => $slabRows): ?>
            <div>
                <h4 class="text-white text-sm font-medium mb-3"><?= e($heading) ?></h4>
                <div class="space-y-3">
                    <?php foreach ($slabRows as $slab): ?>
                        <div class="grid grid-cols-3 gap-3">
                            <input name="slab[<?= (int) $slab['id'] ?>][min]" value="<?= (int) $slab['min_qty'] ?>" class="form-input" inputmode="numeric" aria-label="Minimum quantity">
                            <input name="slab[<?= (int) $slab['id'] ?>][max]" value="<?= (int) $slab['max_qty'] ?>" class="form-input" inputmode="numeric" aria-label="Maximum quantity">
                            <input name="slab[<?= (int) $slab['id'] ?>][price]" value="<?= e(billing_money($slab['unit_price'])) ?>" class="form-input" inputmode="decimal" aria-label="Rate each">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="save_slabs" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save volume rates</button>
    </form>
</section>

<section class="dash-panel mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">Package prices</h3>
        <p class="text-slate-500 text-xs mt-1">These amounts appear on the website and at checkout.</p>
    </div>
    <form method="POST" class="p-5">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($plans as $plan): ?>
                <?php if ((int) $plan['is_active'] !== 1 || $plan['needs_detail'] === 'sms' || $plan['needs_detail'] === 'voice') { continue; } ?>
                <label class="block rounded-2xl border border-slate-800 bg-dark-950 p-4">
                    <span class="block text-white text-sm font-medium mb-1"><?= e($plan['name']) ?></span>
                    <span class="block text-slate-500 text-xs mb-3"><?= e(billing_cycle_label($plan['billing_cycle'])) ?><?= (int) $plan['auto_renew_default'] === 1 ? ' · auto-renew' : '' ?></span>
                    <input name="price[<?= e($plan['code']) ?>]" value="<?= e(billing_money($plan['price'])) ?>" class="form-input" inputmode="decimal">
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="save_prices" value="1" class="mt-5 px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save prices</button>
    </form>
</section>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Client subscriptions</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-800 text-left">
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Client</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Service</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Renewal</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($subscriptions && $subscriptions->num_rows > 0): ?>
                    <?php while ($row = $subscriptions->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-white"><?= e($row['name']) ?></td>
                            <td class="px-4 py-3 text-sm text-slate-300">
                                <?= e($row['service_name']) ?><?= !empty($row['detail_label']) ? ' · ' . e($row['detail_label']) : '' ?>
                                <?php
                                $brief = !empty($row['order_brief']) ? json_decode((string) $row['order_brief'], true) : null;
                                if (is_array($brief)):
                                ?>
                                    <div class="mt-2 space-y-1 text-xs text-slate-400">
                                        <?php foreach ($brief as $label => $value): ?>
                                            <div class="max-h-28 overflow-auto whitespace-pre-wrap"><span class="text-slate-500"><?= e($label) ?>:</span> <?= e($value) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-400"><?= !empty($row['next_renewal']) ? e($row['next_renewal']) : '—' ?> <?= (int) $row['auto_renew'] === 1 ? '· auto' : '' ?></td>
                            <td class="px-4 py-3 text-sm text-slate-300"><?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500 text-sm">No purchased services yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Recent automatic renewals</h3></div>
    <div class="divide-y divide-slate-800">
        <?php if ($renewals && $renewals->num_rows > 0): ?>
            <?php while ($row = $renewals->fetch_assoc()): ?>
                <div class="p-4 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-white text-sm"><?= e(ucfirst($row['result'])) ?> · <?= e(billing_money_label($row['amount'])) ?></p>
                        <p class="text-slate-500 text-xs"><?= e($row['note']) ?></p>
                    </div>
                    <span class="text-slate-500 text-xs"><?= e(date('M d, Y', strtotime($row['created_at']))) ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="p-8 text-center text-slate-500 text-sm">Renewals appear here after the daily run, or when a client opens the portal on a due date.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
