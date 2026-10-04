<?php
require_once __DIR__ . '/../config.php';

require_admin();

if (isset($_GET['export']) && $_GET['export'] === 'wallet') {
    $fromDay = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from']) ? (string) $_GET['from'] : date('Y-m-01');
    $toDay = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to']) ? (string) $_GET['to'] : date('Y-m-d');
    $fromAt = $fromDay . ' 00:00:00';
    $toAt = $toDay . ' 23:59:59';
    $entryStmt = $conn->prepare('SELECT w.id, w.created_at, w.client_id, c.name, c.email, w.kind, w.direction, w.amount, w.status, w.method, w.reference_note FROM wallet_entries w LEFT JOIN client_users c ON c.id = w.client_id WHERE w.created_at >= ? AND w.created_at <= ? ORDER BY w.id ASC');
    $entryStmt->bind_param('ss', $fromAt, $toAt);
    $entryStmt->execute();
    $entryRows = array();
    foreach (db_fetch_all($entryStmt) as $entry) {
        $entryRows[] = array($entry['id'], $entry['created_at'], $entry['client_id'], $entry['name'], $entry['email'], $entry['kind'], $entry['direction'], number_format((float) $entry['amount'], 2, '.', ''), $entry['status'], $entry['method'], $entry['reference_note']);
    }
    $entryStmt->close();
    admin_csv_send('wallet-' . $fromDay . '-to-' . $toDay . '.csv', array('Entry', 'Date', 'Client ID', 'Client', 'Email', 'Kind', 'Direction', 'Amount NPR', 'Status', 'Method', 'Reference'), $entryRows);
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['approve_topup'])) {
        $msg = billing_approve_topup($conn, (int) $_POST['entry_id'])
            ? 'Top-up added to the client wallet.'
            : '';
        $err = $msg === '' ? 'That top-up could not be confirmed.' : '';
    } elseif (isset($_POST['manual_wallet'])) {
        $walletError = billing_admin_wallet_credit(
            $conn,
            isset($_POST['wallet_client']) ? (int) $_POST['wallet_client'] : 0,
            isset($_POST['wallet_amount']) ? (int) $_POST['wallet_amount'] : 0,
            isset($_POST['wallet_note']) ? $_POST['wallet_note'] : ''
        );
        $msg = $walletError === '' ? 'Payment added to the client wallet. They can spend it on services.' : '';
        $err = $walletError;
    } elseif (isset($_POST['reject_topup'])) {
        $msg = billing_reject_topup($conn, (int) $_POST['entry_id']) ? 'Top-up rejected.' : '';
        $err = $msg === '' ? 'That top-up could not be rejected.' : '';
    } elseif (isset($_POST['save_slabs']) && isset($_POST['slab']) && is_array($_POST['slab'])) {
        $slabError = billing_save_slabs($conn, $_POST['slab'], isset($_POST['start']) ? $_POST['start'] : array());
        $msg = $slabError === '' ? 'SMS and voice rates updated. A filled offer rate replaces that row until you clear it. The selected row is the Starts from price on the homepage.' : '';
        $err = $slabError;
    } elseif (isset($_POST['refund_domain'])) {
        $refundError = billing_refund_domain($conn, (int) $_POST['service_id']);
        $msg = $refundError === '' ? 'The domain amount is back in the client wallet, and that order will not renew.' : '';
        $err = $refundError;
    } elseif (isset($_POST['save_panel'])) {
        $panelError = hosting_panel_save(
            $conn,
            isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0,
            isset($_POST['panel_user']) ? $_POST['panel_user'] : '',
            isset($_POST['panel_pass']) ? $_POST['panel_pass'] : '',
            isset($_POST['panel_host']) ? $_POST['panel_host'] : ''
        );
        $msg = $panelError === '' ? 'cPanel login saved. The client opens it from My Services and does not see where the server is bought.' : '';
        $err = $panelError;
    } elseif (isset($_POST['clear_panel'])) {
        $msg = hosting_panel_clear($conn, isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0)
            ? 'cPanel login removed. The client no longer sees the button.'
            : '';
        $err = $msg === '' ? 'That cPanel login could not be removed.' : '';
    } elseif (isset($_POST['save_mail'])) {
        $mailError = mail_login_save(
            $conn,
            isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0,
            isset($_POST['mail_host']) ? $_POST['mail_host'] : '',
            isset($_POST['mail_domain']) ? $_POST['mail_domain'] : '',
            isset($_POST['mail_boxes']) ? $_POST['mail_boxes'] : ''
        );
        $msg = $mailError === '' ? 'Email login is on. The client opens it from My Services.' : '';
        $err = $mailError;
    } elseif (isset($_POST['clear_mail'])) {
        $msg = mail_login_clear($conn, isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0)
            ? 'Email login removed. The client no longer sees the button.'
            : '';
        $err = $msg === '' ? 'That email login could not be removed.' : '';
    } elseif (isset($_POST['save_website'])) {
        $siteError = website_save(
            $conn,
            isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0,
            isset($_POST['site_url']) ? $_POST['site_url'] : '',
            isset($_POST['site_note']) ? $_POST['site_note'] : ''
        );
        $msg = $siteError === '' ? 'Website link saved. The client can open it from My Services.' : '';
        $err = $siteError;
    } elseif (isset($_POST['clear_website'])) {
        $msg = website_clear($conn, isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0)
            ? 'Website link removed. The client no longer sees the button.'
            : '';
        $err = $msg === '' ? 'That website link could not be removed.' : '';
    } elseif (isset($_POST['save_training'])) {
        $visitError = training_save(
            $conn,
            isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0,
            isset($_POST['visit_date']) ? $_POST['visit_date'] : '',
            isset($_POST['visit_done']),
            isset($_POST['visit_place']) ? $_POST['visit_place'] : ''
        );
        $msg = $visitError === '' ? 'The visit date is saved. The client sees it on My Services.' : '';
        $err = $visitError;
    } elseif (isset($_POST['clear_training'])) {
        $msg = training_clear($conn, isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0)
            ? 'The visit date was cleared.'
            : '';
        $err = $msg === '' ? 'That visit date could not be cleared.' : '';
    } elseif (isset($_POST['save_prices']) && isset($_POST['price']) && is_array($_POST['price'])) {
        $saved = 0;
        $failed = 0;
        foreach ($_POST['price'] as $code => $value) {
            $offer = isset($_POST['offer'][$code]) ? (string) $_POST['offer'][$code] : '';
            if (billing_update_price($conn, (string) $code, (string) $value, $offer)) {
                $saved++;
            } else {
                $failed++;
            }
        }
        $msg = $saved > 0 && $failed === 0 ? 'Plan prices updated. New purchases use these amounts, including any offer rate. Existing renewals keep the price from when they were bought.' : '';
        $err = $failed > 0 ? 'Each offer rate must be lower than its regular rate, or left blank. Regular rates must stay above zero.' : ($saved > 0 ? '' : 'Enter a valid price for each plan.');
    }
    if ($err === '' && $msg !== '') {
        $doneTab = 'wallet';
        if (isset($_POST['save_slabs']) || isset($_POST['save_prices'])) {
            $doneTab = 'rates';
        } elseif (isset($_POST['refund_domain'])) {
            $doneTab = 'records';
        } elseif (!isset($_POST['approve_topup']) && !isset($_POST['reject_topup']) && !isset($_POST['manual_wallet'])) {
            $doneTab = 'delivery';
        }
        flash('billing_notice', $msg);
        $doneFind = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        header('Location: billing.php?tab=' . $doneTab . ($doneFind !== '' ? '&q=' . rawurlencode($doneFind) : ''));
        exit;
    }
}
$billingNotice = flash('billing_notice');
if ($msg === '' && $billingNotice !== '') {
    $msg = (string) $billingNotice;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/billing-actions.php';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Billing</h1>
    <p class="text-slate-500 text-sm">Confirm incoming wallet funds. Renewals after that run without a staff action. <a class="text-brand-400" href="manual.php#billing">नेपाली चरण</a></p>
</div>

<?php if ($msg !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php
$billTab = 'wallet';
$billTabs = array('wallet', 'rates', 'delivery', 'records');
if (isset($_POST['save_slabs']) || isset($_POST['save_prices'])) {
    $billTab = 'rates';
} elseif (isset($_POST['save_panel']) || isset($_POST['clear_panel']) || isset($_POST['save_mail']) || isset($_POST['clear_mail']) || isset($_POST['save_website']) || isset($_POST['clear_website']) || isset($_POST['save_training']) || isset($_POST['clear_training'])) {
    $billTab = 'delivery';
} elseif (isset($_POST['refund_domain'])) {
    $billTab = 'records';
} elseif (isset($_GET['tab']) && in_array($_GET['tab'], $billTabs, true)) {
    $billTab = (string) $_GET['tab'];
}
?>
<div x-data="{ tab: '<?= e($billTab) ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" @click="tab='wallet'" :class="tab==='wallet' ? 'is-on' : ''">Wallet</button>
    <button type="button" @click="tab='rates'" :class="tab==='rates' ? 'is-on' : ''">Rates</button>
    <button type="button" @click="tab='delivery'" :class="tab==='delivery' ? 'is-on' : ''">Delivery</button>
    <button type="button" @click="tab='records'" :class="tab==='records' ? 'is-on' : ''">Subscriptions</button>
</div>
<div x-show="tab==='wallet'">
<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <input type="hidden" name="export" value="wallet">
    <label class="text-slate-400 text-xs">From<input type="date" name="from" value="<?= e(date('Y-m-01')) ?>" class="form-input mt-1"></label>
    <label class="text-slate-400 text-xs">To<input type="date" name="to" value="<?= e(date('Y-m-d')) ?>" class="form-input mt-1"></label>
    <button type="submit" class="px-4 py-2 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Download wallet entries (CSV)</button>
</form>
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
                            <td class="px-4 py-3"><a class="text-white text-sm hover:text-brand-300" href="client.php?id=<?= (int) $row['client_id'] ?>"><?= e($row['name']) ?></a><?php $topupEmail = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? (string) $row['email'] : ''; ?><?php if ($topupEmail !== ''): ?><a class="block text-slate-500 text-xs hover:text-brand-300" href="mailto:<?= e($topupEmail) ?>"><?= e($topupEmail) ?></a><?php else: ?><p class="text-slate-500 text-xs"><?= e($row['email']) ?></p><?php endif; ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e(billing_money_label($row['amount'])) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(ucfirst($row['method'])) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= e($row['reference_note']) ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="entry_id" value="<?= (int) $row['id'] ?>">
                                    <button type="submit" name="approve_topup" value="1" class="text-brand-400 text-sm">Confirm</button>
                                    <button type="submit" name="reject_topup" value="1" class="text-red-400 text-sm" onclick="return confirm('Reject this payment? The client wallet is not credited.')">Reject</button>
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
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add a payment yourself</h3></div>
    <form method="POST" class="p-5 grid md:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="wallet_client">Client</label>
            <select id="wallet_client" name="wallet_client" required class="form-input">
                <option value="">Choose</option>
                <?php if ($walletClients): ?>
                    <?php while ($walletClient = $walletClients->fetch_assoc()): ?>
                        <option value="<?= (int) $walletClient['id'] ?>"><?= e($walletClient['name']) ?> · <?= e($walletClient['email']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="wallet_amount">Amount (NPR)</label>
            <input id="wallet_amount" name="wallet_amount" type="number" min="1" max="1000000" required class="form-input" placeholder="5000">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="wallet_note">Where it was received</label>
            <input id="wallet_note" name="wallet_note" type="text" maxlength="160" required class="form-input" placeholder="Cash at the office">
        </div>
        <button type="submit" name="manual_wallet" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add to wallet</button>
    </form>
    <p class="px-5 pb-4 text-slate-500 text-xs">Use this when the client paid in cash or outside eSewa, Khalti, and the bank form. The amount is ready to spend immediately.</p>
</section>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Payments you added</h3></div>
    <?php if ($officePayments && $officePayments->num_rows > 0): ?>
        <div class="divide-y divide-slate-800">
            <?php while ($officeRow = $officePayments->fetch_assoc()): ?>
                <div class="p-4 flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <p class="text-white text-sm"><a class="hover:text-brand-300" href="client.php?id=<?= (int) $officeRow['client_id'] ?>"><?= e($officeRow['name']) ?></a> <?php $officeEmail = filter_var($officeRow['email'], FILTER_VALIDATE_EMAIL) ? (string) $officeRow['email'] : ''; ?><?php if ($officeEmail !== ''): ?><a class="text-slate-500 hover:text-brand-300" href="mailto:<?= e($officeEmail) ?>"><?= e($officeEmail) ?></a><?php else: ?><span class="text-slate-500"><?= e($officeRow['email']) ?></span><?php endif; ?></p>
                        <p class="text-slate-400 text-xs mt-1"><?= e($officeRow['reference_note']) ?></p>
                    </div>
                    <p class="text-white text-sm"><?= e(billing_money_label($officeRow['amount'])) ?> · <?= e(date('M j, Y', strtotime($officeRow['created_at']))) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No office payment has been added yet.</p>
    <?php endif; ?>
</section>
</div>
<div x-show="tab==='rates'" x-cloak>
<section class="dash-panel mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">SMS and voice volume rates</h3>
        <p class="text-slate-500 text-xs mt-1">A quantity inside a row uses that row’s rate. Leave Offer blank to keep the regular rate. The same prices are on <a class="text-brand-400" href="services.php">Services</a>. Choose Starts from on the row that should appear on the homepage.</p>
    </div>
    <form method="POST" class="p-5 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach (array('bulk-sms' => array('Bulk SMS', $smsSlabs), 'bulk-voice' => array('Auto voice calls', $voiceSlabs)) as $slug => $pair): ?>
            <?php
            $heading = $pair[0];
            $slabRows = $pair[1];
            $hasStart = false;
            foreach ($slabRows as $slab) {
                if (!empty($slab['is_start'])) {
                    $hasStart = true;
                    break;
                }
            }
            ?>
            <div>
                <h4 class="text-white text-sm font-medium mb-3"><?= e($heading) ?></h4>
                <div class="space-y-3">
                    <?php foreach ($slabRows as $index => $slab): ?>
                        <?php $checked = !empty($slab['is_start']) || (!$hasStart && $index === 0); ?>
                        <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr_1fr_1fr_1fr] gap-3 items-center">
                            <label class="flex items-center gap-2 text-slate-300 text-sm">
                                <input type="radio" name="start[<?= e($slug) ?>]" value="<?= (int) $slab['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                Starts from
                            </label>
                            <input name="slab[<?= (int) $slab['id'] ?>][min]" value="<?= (int) $slab['min_qty'] ?>" class="form-input" inputmode="numeric" aria-label="Minimum quantity">
                            <input name="slab[<?= (int) $slab['id'] ?>][max]" value="<?= (int) $slab['max_qty'] ?>" class="form-input" inputmode="numeric" aria-label="Maximum quantity">
                            <input name="slab[<?= (int) $slab['id'] ?>][price]" value="<?= e(billing_money($slab['unit_price'])) ?>" class="form-input" inputmode="decimal" aria-label="Regular rate each">
                            <input name="slab[<?= (int) $slab['id'] ?>][offer]" value="<?= !empty($slab['offer_price']) && (float) $slab['offer_price'] > 0 ? e(billing_money($slab['offer_price'])) : '' ?>" class="form-input" inputmode="decimal" placeholder="Offer" aria-label="Offer rate each">
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
        <p class="text-slate-500 text-xs mt-1">These amounts appear on the website and at checkout. Leave Offer blank when there is no special rate. A lower offer crosses out the regular price until you clear it. The homepage Starts from price is the lowest amount a visitor would pay.</p>
    </div>
    <form method="POST" class="p-5">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($plans as $plan): ?>
                <?php if ((int) $plan['is_active'] !== 1 || $plan['needs_detail'] === 'sms' || $plan['needs_detail'] === 'voice') { continue; } ?>
                <label class="block rounded-2xl border border-slate-800 bg-dark-950 p-4">
                    <span class="block text-white text-sm font-medium mb-1"><?= e($plan['name']) ?></span>
                    <span class="block text-slate-500 text-xs mb-3"><?= e(billing_cycle_label($plan['billing_cycle'])) ?><?= (int) $plan['auto_renew_default'] === 1 ? ' · auto-renew' : '' ?></span>
                    <span class="block text-slate-500 text-xs mb-1">Regular rate</span>
                    <input name="price[<?= e($plan['code']) ?>]" value="<?= e(billing_money($plan['price'])) ?>" class="form-input" inputmode="decimal" aria-label="Regular rate">
                    <span class="block text-slate-500 text-xs mt-3 mb-1">Offer rate</span>
                    <input name="offer[<?= e($plan['code']) ?>]" value="<?= !empty($plan['offer_price']) && (float) $plan['offer_price'] > 0 ? e(billing_money($plan['offer_price'])) : '' ?>" class="form-input" inputmode="decimal" placeholder="Leave blank for no offer" aria-label="Offer rate">
                </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="save_prices" value="1" class="mt-5 px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save prices</button>
    </form>
</section>
</div>
<div x-show="tab==='delivery'" x-cloak>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="hidden" name="tab" value="delivery">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Client or service in the sections below">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Hosting logins</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">After a hosting plan is active, save the cPanel username and password here. Leave the address blank so the client opens cPanel on their own domain. The client never sees where the server was bought.</p>
        <?php if (!$hostingLogins): ?>
            <p class="text-slate-500 text-sm"><?= $find === '' ? 'No active hosting yet. Orders still waiting for a login stay in this list.' : 'No hosting matches that search.' ?></p>
        <?php endif; ?>
        <?php foreach ($hostingLogins as $row): ?>
            <?php $openOn = hosting_panel_label($row); ?>
            <form method="POST" class="rounded-xl border border-slate-800 p-4 space-y-2 max-w-lg">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                <p class="text-white text-sm font-medium"><?= e($row['name']) ?> · <?= e($row['service_name']) ?></p>
                <p class="text-slate-500 text-xs"><?php if ($openOn !== ''): ?>Client sees <?= e($openOn) ?>.<?php else: ?>No domain on this order yet.<?php endif; ?> <?php $loginHost = hosting_panel_domain($row); if ($loginHost !== '' && $loginHost !== $openOn): ?>The address bar will show <?= e($loginHost) ?> unless this address is cleared.<?php endif; ?> <?= hosting_panel_ready($row) ? 'Login is ready.' : 'The button stays hidden until a username and password are saved.' ?></p>
                <input type="text" name="panel_user" value="<?= e($row['panel_user']) ?>" maxlength="16" class="form-input" placeholder="cPanel username" autocomplete="off">
                <input type="password" name="panel_pass" maxlength="80" class="form-input" placeholder="<?= $row['panel_pass'] !== '' && $row['panel_pass'] !== null ? 'Saved. Leave blank to keep it.' : 'cPanel password' ?>" autocomplete="new-password">
                <input type="text" name="panel_host" value="<?= e($row['panel_host']) ?>" maxlength="253" class="form-input" placeholder="Blank uses the client domain" autocomplete="off">
                <div class="flex items-center gap-4">
                    <button type="submit" name="save_panel" value="1" class="text-brand-400 text-xs">Save cPanel login</button>
                    <?php if (trim((string) $row['panel_user']) !== '' || (string) $row['panel_pass'] !== ''): ?>
                        <button type="submit" name="clear_panel" value="1" class="text-slate-500 text-xs" onclick="return confirm('Remove this cPanel login from the client page?')">Remove login</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Email logins</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">After the mailboxes exist in Zoho, show the login here. In Zoho, set the custom login to mail.the-client-domain and point that name at Zoho first. Leave the address blank. The client opens that name and does not see a Zoho address. A Zoho address cannot be saved.</p>
        <?php if (!$mailLogins): ?>
            <p class="text-slate-500 text-sm"><?= $find === '' ? 'No active mailbox plan yet. Orders still waiting to be shown stay in this list.' : 'No mailbox matches that search.' ?></p>
        <?php endif; ?>
        <?php foreach ($mailLogins as $row): ?>
            <?php
            $mailDomain = mail_login_domain($row);
            $mailHost = mail_login_host($row);
            $mailBoxes = mail_login_boxes($row);
            $mailLocals = array();
            foreach ($mailBoxes as $mailBox) {
                $mailLocals[] = strstr($mailBox, '@', true);
            }
            ?>
            <form method="POST" class="rounded-xl border border-slate-800 p-4 space-y-2 max-w-lg">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                <p class="text-white text-sm font-medium"><?= e($row['name']) ?> · <?= e($row['service_name']) ?></p>
                <p class="text-slate-500 text-xs">Up to <?= (int) mail_box_limit((string) $row['plan_code']) ?> mailbox<?= mail_box_limit((string) $row['plan_code']) === 1 ? '' : 'es' ?>. <?php if ($mailDomain !== ''): ?>Inbox opens on <?= e($mailHost !== '' ? $mailHost : 'mail.' . $mailDomain) ?>.<?php endif; ?> <?= (string) $row['panel_user'] === 'open' ? 'Login is on.' : 'The button stays hidden until you show it.' ?> The password is sent to the client and is not stored here.</p>
                <input type="text" name="mail_domain" value="<?= e($mailDomain) ?>" maxlength="253" class="form-input" placeholder="shop.com.np" autocomplete="off">
                <input type="text" name="mail_boxes" value="<?= e(implode(', ', $mailLocals)) ?>" maxlength="300" class="form-input" placeholder="info, sales" autocomplete="off">
                <input type="text" name="mail_host" value="<?= e($row['panel_host']) ?>" maxlength="253" class="form-input" placeholder="Blank uses mail.the-client-domain" autocomplete="off">
                <div class="flex items-center gap-4">
                    <button type="submit" name="save_mail" value="1" class="text-brand-400 text-xs">Show email login</button>
                    <?php if ((string) $row['panel_user'] === 'open'): ?>
                        <button type="submit" name="clear_mail" value="1" class="text-slate-500 text-xs" onclick="return confirm('Remove this email login from the client page?')">Remove login</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Website links</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">When a booked website is published, save its address here. Use the client’s own domain. The client opens that address from My Services.</p>
        <?php if (!$websiteJobs): ?>
            <p class="text-slate-500 text-sm"><?= $find === '' ? 'No booked website yet. Orders still waiting for a link stay in this list.' : 'No website matches that search.' ?></p>
        <?php endif; ?>
        <?php foreach ($websiteJobs as $row): ?>
            <form method="POST" class="rounded-xl border border-slate-800 p-4 space-y-2 max-w-lg">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                <p class="text-white text-sm font-medium"><?= e($row['name']) ?> · <?= e($row['service_name']) ?></p>
                <p class="text-slate-500 text-xs"><?= website_ready($row) ? 'The client can open this website.' : 'The button stays hidden until an address is saved.' ?></p>
                <input type="text" name="site_url" value="<?= e($row['panel_host']) ?>" maxlength="253" class="form-input" placeholder="https://their-domain.com.np" autocomplete="off">
                <input type="text" name="site_note" value="<?= e(delivery_brief_value($row, 'Note')) ?>" maxlength="180" class="form-input" placeholder="What is live, optional">
                <div class="flex items-center gap-4">
                    <button type="submit" name="save_website" value="1" class="text-brand-400 text-xs">Save website link</button>
                    <?php if ((string) $row['panel_user'] === 'open'): ?>
                        <button type="submit" name="clear_website" value="1" class="text-slate-500 text-xs" onclick="return confirm('Remove this website link from the client page?')">Remove link</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Training visits</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">After a training booking, confirm the visit date the client will see. Mark it complete when the visit is finished.</p>
        <?php if (!$trainingJobs): ?>
            <p class="text-slate-500 text-sm"><?= $find === '' ? 'No booked training yet. Visits still waiting for a date stay in this list.' : 'No training matches that search.' ?></p>
        <?php endif; ?>
        <?php foreach ($trainingJobs as $row): ?>
            <?php $visitDate = training_date($row); ?>
            <form method="POST" class="rounded-xl border border-slate-800 p-4 space-y-2 max-w-lg">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                <p class="text-white text-sm font-medium"><?= e($row['name']) ?> · <?= e($row['service_name']) ?></p>
                <p class="text-slate-500 text-xs"><?php if ((string) $row['panel_user'] === 'done'): ?>Marked complete.<?php elseif ((string) $row['panel_user'] === 'confirmed' && $visitDate !== ''): ?>The client sees <?= e(date('M j, Y', strtotime($visitDate))) ?>.<?php else: ?>The client still sees this as booked.<?php endif; ?></p>
                <input type="date" name="visit_date" value="<?= e($visitDate) ?>" class="form-input">
                <input type="text" name="visit_place" value="<?= e(delivery_brief_value($row, 'Place')) ?>" maxlength="180" class="form-input" placeholder="Place, such as the client office">
                <label class="flex items-center gap-2 text-slate-400 text-xs"><input type="checkbox" name="visit_done" value="1" <?= (string) $row['panel_user'] === 'done' ? 'checked' : '' ?>> Visit is complete</label>
                <div class="flex items-center gap-4">
                    <button type="submit" name="save_training" value="1" class="text-brand-400 text-xs">Save visit</button>
                    <?php if ((string) $row['panel_user'] !== ''): ?>
                        <button type="submit" name="clear_training" value="1" class="text-slate-500 text-xs">Clear date</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>
</div>
<div x-show="tab==='records'" x-cloak>
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
                            <td class="px-4 py-3 text-sm text-white"><a class="hover:text-brand-300" href="client.php?id=<?= (int) $row['client_id'] ?>"><?= e($row['name']) ?></a></td>
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
                            <td class="px-4 py-3 text-sm text-slate-300">
                                <?= e(ucfirst(str_replace('_', ' ', $row['status']))) ?>
                                <?php if (($row['plan_code'] === 'domain-com' || $row['plan_code'] === 'domain-np') && $row['status'] === 'active'): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="service_id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" name="refund_domain" value="1" class="text-yellow-300 text-xs" onclick="return confirm('Return this domain payment to the client wallet?')">Return to wallet</button>
                                    </form>
                                <?php endif; ?>
                            </td>
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
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
