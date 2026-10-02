<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$msg = '';
$err = '';
$amountValue = isset($_GET['amount']) ? (string) (int) $_GET['amount'] : '';
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
        $amountValue = '';
        $referenceValue = '';
    }
}

$balance = billing_balance($conn, $cid);
$units = billing_unit_balances($conn, $cid);
$instructions = billing_payment_instructions();
$history = $conn->prepare('SELECT * FROM wallet_entries WHERE client_id = ? ORDER BY id DESC LIMIT 20');
$history->bind_param('i', $cid);
$history->execute();
$entries = $history->get_result();
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Wallet</h1>
    <p class="text-slate-500 text-sm">Funds here pay for new services and automatic renewals.</p>
</div>

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
        <div class="dash-stat-value"><?= number_format((int) $units['voice_calls'] + (int) $units['voice_minutes']) ?></div>
        <div class="dash-stat-label">Voice calls</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add funds</h3></div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label for="amount" class="block text-slate-400 text-xs font-medium mb-1.5">Amount (NPR)</label>
                <input id="amount" name="amount" inputmode="numeric" required value="<?= e($amountValue) ?>" class="form-input" placeholder="5000">
            </div>
            <div>
                <label for="method" class="block text-slate-400 text-xs font-medium mb-1.5">Payment method</label>
                <select id="method" name="method" class="form-input">
                    <option value="esewa" <?= $methodValue === 'esewa' ? 'selected' : '' ?>>eSewa</option>
                    <option value="khalti" <?= $methodValue === 'khalti' ? 'selected' : '' ?>>Khalti</option>
                    <option value="bank" <?= $methodValue === 'bank' ? 'selected' : '' ?>>Bank transfer</option>
                </select>
            </div>
            <div>
                <label for="reference" class="block text-slate-400 text-xs font-medium mb-1.5">Transaction reference</label>
                <input id="reference" name="reference" required maxlength="80" value="<?= e($referenceValue) ?>" class="form-input" placeholder="Transaction or voucher code">
            </div>
            <button type="submit" name="request_topup" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit top-up</button>
        </form>
    </section>
    <section class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Where to pay</h3></div>
        <div class="p-5 space-y-4 text-sm text-slate-400">
            <p><strong class="text-white">eSewa.</strong> <?= e($instructions['esewa']) ?></p>
            <p><strong class="text-white">Khalti.</strong> <?= e($instructions['khalti']) ?></p>
            <p><strong class="text-white">Bank.</strong> <?= e($instructions['bank']) ?></p>
            <p>After confirmation, domain, hosting, email, and monthly credit plans renew by themselves while the balance covers the price.</p>
        </div>
    </section>
</div>

<section class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Recent wallet activity</h3></div>
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
                <?php if ($entries && $entries->num_rows > 0): ?>
                    <?php while ($entry = $entries->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(date('M d, Y', strtotime($entry['created_at']))) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e(ucfirst($entry['kind'])) ?><?= $entry['reference_note'] !== '' ? ' · ' . e($entry['reference_note']) : '' ?></td>
                            <td class="px-4 py-3 text-sm <?= $entry['direction'] === 'credit' ? 'text-green-400' : 'text-white' ?>"><?= $entry['direction'] === 'credit' ? '+' : '−' ?><?= e(billing_money_label($entry['amount'])) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-sm"><?= e(ucfirst($entry['status'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500 text-sm">No wallet activity yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$history->close();
require_once __DIR__ . '/includes/footer.php';
?>
