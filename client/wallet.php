<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$msg = '';
$err = '';
$amountValue = isset($_GET['amount']) ? (string) (int) $_GET['amount'] : '';
$forDomain = isset($_GET['for']) && $_GET['for'] === 'domain';
if ($forDomain) {
    $_SESSION['wallet_for'] = 'domain';
}
$methodValue = 'esewa';
$referenceValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_topup'])) {
    verify_csrf();
    $amountValue = isset($_POST['amount']) ? (string) $_POST['amount'] : '';
    $methodValue = isset($_POST['method']) ? (string) $_POST['method'] : '';
    $referenceValue = isset($_POST['reference']) ? (string) $_POST['reference'] : '';
    $err = billing_request_topup($conn, $cid, $amountValue, $methodValue, $referenceValue);
    if ($err === '') {
        $msg = 'Top-up submitted. It is added to your wallet after a quick confirmation, and renewals continue from there without another request.';
        if (isset($_SESSION['wallet_for']) && $_SESSION['wallet_for'] === 'domain') {
            $msg = 'Top-up submitted. After it is confirmed, open My domains and pay the yearly bill.';
            unset($_SESSION['wallet_for']);
        }
        $amountValue = '';
        $referenceValue = '';
    }
}

$balance = billing_balance($conn, $cid);
$units = billing_unit_balances($conn, $cid);
$paymentMethods = billing_payment_methods($conn);
$onlineMethods = array();
$manualMethods = array();
foreach ($paymentMethods as $paymentMethod) {
    if ($paymentMethod['group'] === 'manual') {
        $manualMethods[] = $paymentMethod;
    } else {
        $onlineMethods[] = $paymentMethod;
    }
}
$methodCodes = array();
foreach ($paymentMethods as $paymentMethod) {
    $methodCodes[] = $paymentMethod['code'];
}
if (!in_array($methodValue, $methodCodes, true)) {
    $methodValue = $methodCodes ? $methodCodes[0] : '';
}
$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$entries = array();
$entrySeen = array();
if ($find !== '') {
    $like = '%' . $find . '%';
    $history = $conn->prepare('SELECT * FROM wallet_entries WHERE client_id = ? AND (reference_note LIKE ? OR kind LIKE ? OR status LIKE ? OR method LIKE ?) ORDER BY id DESC LIMIT 50');
    $history->bind_param('issss', $cid, $like, $like, $like, $like);
    $history->execute();
    $entries = db_fetch_all($history);
    $history->close();
} else {
    foreach (array(
        'SELECT * FROM wallet_entries WHERE client_id = ? AND status = \'pending\' ORDER BY id DESC',
        'SELECT * FROM wallet_entries WHERE client_id = ? ORDER BY id DESC LIMIT 30'
    ) as $historySql) {
        $history = $conn->prepare($historySql);
        $history->bind_param('i', $cid);
        $history->execute();
        foreach (db_fetch_all($history) as $entryRow) {
            $entryId = (int) $entryRow['id'];
            if (isset($entrySeen[$entryId])) {
                continue;
            }
            $entrySeen[$entryId] = true;
            $entries[] = $entryRow;
        }
        $history->close();
    }
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Wallet</h1>
    <p class="text-slate-500 text-sm">Funds here pay for new services and automatic renewals.</p>
</div>
<?php if ($forDomain && $amountValue !== '' && $amountValue !== '0'): ?>
    <div class="mb-4 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-sm text-yellow-100">
        Add NPR <?= e(number_format((int) $amountValue)) ?> for the domain year. After this top-up is confirmed, open <a class="text-brand-300" href="domains.php">My domains</a> and pay the bill.
    </div>
<?php endif; ?>

<?php if ($msg !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-4 mb-8">
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= e(billing_money_label($balance)) ?></div>
        <div class="dash-stat-label">Available balance</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= number_format($units['sms']) ?></div>
        <div class="dash-stat-label">SMS credits</div>
    </div>
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= number_format((int) $units['voice_calls']) ?></div>
        <div class="dash-stat-label">Voice calls</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add funds</h3></div>
        <?php if (!$paymentMethods): ?>
            <div class="p-5 text-sm text-slate-400">Payment details are not published yet. Online methods need an eSewa or Khalti ID, and manual payment needs the bank account, saved in Admin settings.</div>
        <?php else: ?>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label for="amount" class="block text-slate-400 text-xs font-medium mb-1.5">Amount (NPR)</label>
                <input id="amount" name="amount" inputmode="numeric" required value="<?= e($amountValue) ?>" class="form-input" placeholder="5000">
            </div>
            <div>
                <label for="method" class="block text-slate-400 text-xs font-medium mb-1.5">Payment method</label>
                <select id="method" name="method" class="form-input">
                    <?php foreach ($paymentMethods as $paymentMethod): ?>
                        <option value="<?= e($paymentMethod['code']) ?>" <?= $methodValue === $paymentMethod['code'] ? 'selected' : '' ?>><?= e($paymentMethod['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="reference" class="block text-slate-400 text-xs font-medium mb-1.5">Transaction reference</label>
                <input id="reference" name="reference" required maxlength="80" value="<?= e($referenceValue) ?>" class="form-input" placeholder="Transaction or voucher code">
            </div>
            <button type="submit" name="request_topup" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit top-up</button>
        </form>
        <?php endif; ?>
    </section>
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Where to pay</h3></div>
        <div class="p-5 space-y-4 text-sm text-slate-400">
            <?php if ($onlineMethods): ?>
                <p class="text-white font-medium">Online payment</p>
                <?php foreach ($onlineMethods as $paymentMethod): ?>
                    <p><strong class="text-white"><?= e($paymentMethod['label']) ?>.</strong> <?= e($paymentMethod['instruction']) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($manualMethods): ?>
                <p class="text-white font-medium">Manual payment</p>
                <?php foreach ($manualMethods as $paymentMethod): ?>
                    <p class="whitespace-pre-wrap"><strong class="text-white"><?= e($paymentMethod['label']) ?>.</strong> <?= e($paymentMethod['instruction']) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!$paymentMethods): ?>
                <p>No payment method is available until the details are saved.</p>
            <?php endif; ?>
            <p>The first top-up is confirmed once. After that, the wallet pays the service bill, which is the list price plus 13% VAT.</p>
        </div>
    </section>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Note, type, or status">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<section class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white"><?= $find === '' ? 'Wallet activity' : 'Matches for “' . e($find) . '”' ?></h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-800 text-left">
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">When</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Type</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Amount</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($entries): ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(date('M d, Y', strtotime($entry['created_at']))) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e(ucfirst($entry['kind'])) ?><?= $entry['reference_note'] !== '' ? ' · ' . e($entry['reference_note']) : '' ?></td>
                            <td class="px-4 py-3 text-sm <?= $entry['direction'] === 'credit' ? 'text-green-400' : 'text-white' ?>"><?= $entry['direction'] === 'credit' ? '+' : '−' ?><?= e(billing_money_label($entry['amount'])) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(ucfirst($entry['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500 text-sm"><?= $find === '' ? 'No wallet activity yet. A waiting top-up stays in this list.' : 'No wallet row matches that search.' ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
