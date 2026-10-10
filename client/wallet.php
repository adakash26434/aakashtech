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
require_once __DIR__ . '/../includes/shop-view.php';
$type = isset($_GET['type']) && in_array($_GET['type'], array('in', 'out', 'waiting'), true) ? (string) $_GET['type'] : 'all';
$renewalRows = $conn->prepare('SELECT * FROM client_services WHERE client_id = ?');
$renewalRows->bind_param('i', $cid);
$renewalRows->execute();
$renewal = service_renewal_summary(db_fetch_all($renewalRows) ?: array(), $balance, 30);
$renewalRows->close();
$quickAmounts = wallet_quick_amounts($renewal['short_by']);
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
$allEntries = $entries;
$totals = wallet_totals($allEntries);
if ($type !== 'all') {
    $entries = array_values(array_filter($entries, function ($row) use ($type) {
        if ($type === 'waiting') { return $row['status'] === 'pending'; }
        return ($type === 'in') === ($row['direction'] === 'credit') && $row['status'] !== 'pending';
    }));
}
?>
<div class="shop-head">
    <div>
        <h1 class="shop-title">Wallet</h1>
        <p class="shop-sub">Money here pays for new services and for renewals. <a class="text-brand-400" href="manual.php#wallet">नेपाली चरण</a></p>
    </div>
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

<section class="wal-hero" aria-label="Balance">
    <div class="wal-balance">
        <span>Available balance</span>
        <strong><?= e(billing_money_label($balance)) ?></strong>
        <?php if ($totals['waiting'] > 0): ?><em class="is-wait">+ <?= e(billing_money_label($totals['waiting'])) ?> waiting to be confirmed</em><?php endif; ?>
        <button type="button" class="co-pay" onclick="document.getElementById('wallet-add').scrollIntoView({behavior:'smooth'});document.getElementById('amount').focus();">Add funds</button>
    </div>
    <dl class="wal-credits">
        <div><dt>SMS credits</dt><dd><?= number_format($units['sms']) ?></dd></div>
        <div><dt>Voice calls</dt><dd><?= number_format((int) $units['voice_calls']) ?></dd></div>
    </dl>
    <?php if ($renewal['items']): ?>
        <div class="wal-renew <?= $renewal['short_by'] > 0 ? 'is-short' : 'is-ok' ?>">
            <b><?= $renewal['short_by'] > 0 ? 'Renewals need NPR ' . e(number_format($renewal['short_by'], 2)) . ' more' : 'Renewals are covered' ?></b>
            <span><?= count($renewal['items']) ?> in the next 30 days, <?= e(billing_money_label($renewal['total'])) ?> in all.</span>
        </div>
    <?php endif; ?>
</section>

<div id="wallet-add" x-data="{ tab: '<?= ($err !== '' || !empty($_GET['amount'])) ? 'work' : 'list' ?>' }">
<div class="portal-tabs" role="tablist" @click.window="if ($event.target.id === 'amount') tab = 'work'">
    <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">Activity</button>
    <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">Add funds</button>
</div>
<div x-show="tab==='work'" x-cloak>
<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add funds</h3></div>
        <?php if (!$paymentMethods): ?>
            <div class="p-5 text-sm text-slate-400">Payment details are not published yet. Online methods need an eSewa or Khalti ID, and manual payment needs the bank account, saved in Admin settings.</div>
        <?php else: ?>
        <form method="POST" class="p-5 space-y-4 wal-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <fieldset class="wal-step"><legend><b>1</b> Pay with</legend>
                <div class="wal-methods">
                <?php foreach ($paymentMethods as $paymentMethod): ?>
                    <label class="wal-method"><input type="radio" name="method" value="<?= e($paymentMethod['code']) ?>" <?= $methodValue === $paymentMethod['code'] ? 'checked' : '' ?> required>
                        <span><b><?= e($paymentMethod['label']) ?></b><small class="whitespace-pre-wrap"><?= e($paymentMethod['instruction']) ?></small></span>
                    </label>
                <?php endforeach; ?>
                </div>
            </fieldset>
            <fieldset class="wal-step"><legend><b>2</b> How much</legend>
                <label for="amount" class="block">Amount (NPR) <span class="req-mark" aria-hidden="true">*</span></label>
                <input id="amount" name="amount" inputmode="numeric" required value="<?= e($amountValue) ?>" class="form-input" placeholder="5000" autocomplete="off">
                <div class="wal-chips" role="group" aria-label="Quick amounts">
                    <?php foreach ($quickAmounts as $chip): ?>
                        <button type="button" data-amount="<?= (int) $chip['amount'] ?>"><?= e($chip['label']) ?><?= $chip['note'] !== '' ? '<small>' . e($chip['note']) . '</small>' : '' ?></button>
                    <?php endforeach; ?>
                </div>
                <small class="field-hint">From NPR 100 to NPR 1,000,000. Services are billed at the list price plus 13% VAT.</small>
            </fieldset>
            <fieldset class="wal-step"><legend><b>3</b> After you have paid</legend>
                <label for="reference" class="block">Transaction ID or voucher number <span class="req-mark" aria-hidden="true">*</span></label>
                <input id="reference" name="reference" required maxlength="80" value="<?= e($referenceValue) ?>" class="form-input" placeholder="Copy it from the eSewa, Khalti or bank message" autocomplete="off">
                <small class="field-hint">Send the money first using the details above, then enter its ID here. We confirm it, usually the same working day, and the amount is added to your balance.</small>
            </fieldset>
            <button type="submit" name="request_topup" value="1" class="co-pay">Submit top-up</button>
        </form>
        <?php endif; ?>
    </section>
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">How it works</h3></div>
        <ol class="co-next" style="padding:18px 18px 18px 36px">
            <li>Pay using one of the methods on the left.</li>
            <li>Enter the amount and the transaction ID, then submit.</li>
            <li>We confirm it. You can see it as <b>Waiting for confirmation</b> in your activity.</li>
            <li>Once confirmed, the amount is in your wallet and renewals continue from it without another request.</li>
        </ol>
        <?php if (!$paymentMethods): ?><p class="p-5 text-sm text-slate-400">No payment method is available until the details are saved.</p><?php endif; ?>
    </section>
</div>
</div>
<div x-show="tab==='list'">
<nav class="shop-tabs" aria-label="Show" style="position:static;border:0;padding:0 0 12px">
    <?php foreach (array('all' => 'All', 'in' => 'Money in', 'out' => 'Money out', 'waiting' => 'Waiting') as $key => $label): ?>
        <a href="wallet.php?type=<?= e($key) ?><?= $find !== '' ? '&amp;q=' . rawurlencode($find) : '' ?>" class="<?= $type === $key ? 'is-on' : '' ?>"<?= $type === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Search by note, type or status">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<p class="wal-sum">Last 30 entries below: money in <b class="is-in"><?= e(billing_money_label($totals['in'])) ?></b> · money out <b><?= e(billing_money_label($totals['out'])) ?></b></p>
<section class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white"><?= $find === '' ? 'Wallet activity' : 'Matches for “' . e($find) . '”' ?></h3></div>
    <div class="overflow-x-auto">
        <table class="w-full wal-table">
            <thead><tr><th scope="col">When</th><th scope="col">What</th><th scope="col">Amount</th><th scope="col">Status</th></tr></thead>
            <tbody>
            <?php if ($entries): ?>
                <?php foreach ($entries as $entry): $w = wallet_entry_words($entry); ?>
                    <tr class="<?= $entry['status'] === 'pending' ? 'is-pending' : '' ?>">
                        <td data-label="When"><?= e(date('j M Y', strtotime($entry['created_at']))) ?><small><?= e(date('H:i', strtotime($entry['created_at']))) ?></small></td>
                        <td data-label="What"><b><?= e($w['title']) ?></b><?= $w['note'] !== '' ? '<small>' . e($w['note']) . '</small>' : '' ?><?= $entry['method'] !== '' && $entry['kind'] === 'topup' ? '<small>via ' . e($entry['method']) . '</small>' : '' ?></td>
                        <td data-label="Amount" class="<?= $w['in'] ? 'is-in' : '' ?>"><?= $w['in'] ? '+' : '−' ?><?= e(billing_money_label($entry['amount'])) ?></td>
                        <td data-label="Status"><span class="svc-pill is-<?= e($w['tone']) ?>"><?= e($w['state']) ?></span><?php if ($entry['status'] === 'completed'): ?> <a class="smsr-link" href="receipt.php?id=<?= (int) $entry['id'] ?>" target="_blank" rel="noopener">Receipt</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="wal-empty"><?= $find === '' && $type === 'all' ? 'No wallet activity yet. After you add funds it appears here, including a top-up that is still waiting.' : 'Nothing matches. Clear the search or choose All.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</div>
</div>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('[data-amount]');
    if (!b) { return; }
    var input = document.getElementById('amount');
    if (input) { input.value = b.getAttribute('data-amount'); input.focus(); }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
