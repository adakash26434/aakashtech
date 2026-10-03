<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/domain-check.php';

$cid = (int) get_client_id();
$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_domain'])) {
    verify_csrf();
    $error = domain_pay_request($conn, $cid, isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0);
    if ($error === '') {
        $notice = 'The yearly bill is paid. The team can now register this name.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_domain'])) {
    verify_csrf();
    $error = domain_cancel_request($conn, $cid, isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0);
    if ($error === '') {
        $notice = 'The request was cancelled before payment. The name is free to request again.';
    }
}
$rows = domain_client_requests($conn, $cid);
$balance = billing_balance($conn, $cid);
$fundsLeft = $balance;
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Domain requests</h1>
    <p class="text-slate-500 text-sm">Pay the yearly bill from the wallet. The team registers the name after that, then the status becomes Active and the year starts. <a class="text-brand-400" href="../domain.php">Register a name</a> · <a class="text-brand-400" href="../whois.php">WHOIS check up</a> · <a class="text-brand-400" href="manual.php#domain">नेपाली चरण</a></p>
</div>
<?php if ($notice): ?><div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div><?php endif; ?>
<?php if (!$rows): ?>
    <div class="dash-panel"><div class="p-6 text-slate-400 text-sm">No domain request yet.</div></div>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
    <?php
    $status = (string) $row['status'];
    $price = isset($row['price']) ? (float) $row['price'] : 0;
    $paid = $status === 'paid' || $status === 'active' || ($status === 'requested' && $price <= 0);
    $registering = $status === 'paid' || ($status === 'requested' && $price <= 0);
    ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <h2 class="font-heading font-semibold text-white text-lg"><?= e($row['domain_name']) ?> <a class="text-brand-400 text-xs font-medium" href="../whois.php?name=<?= rawurlencode((string) $row['domain_name']) ?>">See the record</a></h2>
                <span class="text-sm <?= $status === 'active' ? 'text-green-400' : ($status === 'declined' ? 'text-red-300' : 'text-yellow-300') ?>"><?php
                    if ($status === 'requested' && $price > 0) {
                        echo 'Waiting for payment';
                    } elseif ($status === 'paid') {
                        echo 'Waiting for registration';
                    } else {
                        echo e(ucfirst($status));
                    }
                ?></span>
            </div>
            <p class="text-slate-400 text-sm mb-4"><?= (isset($row['holder_kind']) && $row['holder_kind'] === 'organization') ? 'Organization' : 'Individual' ?> · <?= e($row['holder_name']) ?><?php if (!empty($row['holder_address'])): ?> · <?= e($row['holder_address']) ?><?php endif; ?><?php if ($price > 0): ?> · <?= e(billing_money_label($price)) ?> for one year<?php endif; ?></p>
            <?php if ($status !== 'declined'): ?>
            <ol class="domain-steps">
                <li class="is-done">Name checked as available</li>
                <li class="is-done">Request sent<?= $row['document_path'] !== '' ? ' with the required document' : '' ?></li>
                <li class="<?= $paid ? 'is-done' : 'is-current' ?>"><?= $price > 0 ? 'Yearly bill paid from the wallet' : 'No separate bill on this request' ?></li>
                <li class="<?= $status === 'active' ? 'is-done' : ($registering ? 'is-current' : '') ?>">The team registers this name</li>
                <li class="<?= $status === 'active' ? 'is-done' : '' ?>">Active, then the year renews from the wallet<?php if ($status === 'active' && !empty($row['activated_at'])): ?> on <?= e(date('M j, Y', strtotime($row['activated_at'] . ' +1 year'))) ?><?php endif; ?></li>
            </ol>
            <?php endif; ?>
            <?php if ($status === 'declined' && $row['admin_note'] !== ''): ?>
                <p class="text-yellow-200 text-sm mt-4"><?= e($row['admin_note']) ?></p>
            <?php endif; ?>
            <?php if ($status === 'requested' && $price > 0): ?>
                <p class="text-slate-300 text-sm mt-4">Wallet <?= e(billing_money_label($balance)) ?>. This year is <?= e(billing_money_label($price)) ?>.</p>
                <?php if ($fundsLeft + 0.001 >= $price): ?>
                    <?php $fundsLeft -= $price; ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" name="pay_domain" value="1" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Pay the yearly bill</button>
                    </form>
                <?php else: ?>
                    <?php $need = (int) ceil(max(0, $price - $fundsLeft)); ?>
                    <p class="mt-3"><a class="text-brand-400 text-sm" href="wallet.php?amount=<?= $need ?>&amp;for=domain">Add NPR <?= e(number_format($need)) ?>, then pay this bill</a></p>
                <?php endif; ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="cancel_domain" value="1" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300" onclick="return confirm('Cancel this domain request?')">Cancel this request</button>
                </form>
            <?php endif; ?>
            <?php if ($status === 'paid' || $status === 'active'): ?>
                <p class="text-slate-400 text-sm mt-4">Hosting, domain email, and a website stay separate. <a class="text-brand-400" href="shop.php?service=hosting-server">Hosting</a> · <a class="text-brand-400" href="shop.php?service=professional-email">Domain email</a> · <a class="text-brand-400" href="shop.php?service=custom-websites">Website</a></p>
            <?php endif; ?>
            <?php if ($row['document_path'] !== ''): ?>
                <p class="mt-4"><a class="text-brand-400 text-sm" href="domain-file.php?id=<?= (int) $row['id'] ?>">View the attached document</a></p>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
