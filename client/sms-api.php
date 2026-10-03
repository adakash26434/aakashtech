<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$msg = '';
$err = '';
$plainToken = '';
if (!empty($_SESSION['sms_plain_token'])) {
    $plainToken = (string) $_SESSION['sms_plain_token'];
    unset($_SESSION['sms_plain_token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_token'])) {
    verify_csrf();
    if (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before a token can be created.';
    } else {
        $created = sms_create_token(
            $conn,
            $cid,
            isset($_POST['label']) ? $_POST['label'] : '',
            isset($_POST['allowed_ips']) ? $_POST['allowed_ips'] : ''
        );
        if (!empty($created['ok'])) {
            $_SESSION['sms_plain_token'] = $created['token'];
            header('Location: sms-api.php');
            exit;
        }
        $err = $created['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_token'])) {
    verify_csrf();
    $tokenId = isset($_POST['token_id']) ? (int) $_POST['token_id'] : 0;
    if (sms_revoke_token($conn, $cid, $tokenId)) {
        $msg = 'Token revoked. Calls with that token now fail.';
    } else {
        $err = 'That token could not be revoked.';
    }
}

$stmt = $conn->prepare('SELECT id, label, token_prefix, status, allowed_ips, last_used_at, created_at FROM sms_api_tokens WHERE client_id = ? ORDER BY id DESC');
$stmt->bind_param('i', $cid);
$stmt->execute();
$tokens = db_fetch_all($stmt);
$stmt->close();
$kycReady = billing_kyc_approved($conn, $cid);
$origin = sms_api_origin();
$sendUrl = ($origin !== '' ? $origin : '') . '/api/sms/send';
$creditUrl = ($origin !== '' ? $origin : '') . '/api/sms/credit';
$route = sms_client_route($conn, $cid);
$apiBalance = billing_unit_balances($conn, $cid);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS API</h1>
    <p class="text-slate-500 text-sm">Create a token and call it from your website or app. People receive the SMS from Aakash Technologies. Your credits fall on each accepted message. <?= number_format((int) $apiBalance['sms']) ?> credits left.</p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php if ($plainToken !== ''): ?>
    <div class="mb-6 p-5 bg-brand-500/10 border border-brand-500/30 rounded-xl">
        <p class="text-white text-sm font-medium mb-2">Copy this token now. It is not shown again.</p>
        <div class="flex flex-wrap items-start gap-3">
            <code id="sms-new-token" class="block text-brand-300 text-sm break-all"><?= e($plainToken) ?></code>
            <button type="button" class="shrink-0 px-3 py-1.5 bg-brand-500 hover:bg-brand-400 text-white text-xs rounded-lg" onclick="navigator.clipboard.writeText(document.getElementById('sms-new-token').textContent)">Copy token</button>
        </div>
    </div>
<?php endif; ?>

<?php if (!$kycReady): ?>
    <div class="dash-panel mb-6">
        <div class="p-6">
            <p class="text-slate-300 text-sm mb-4">A token can be created after identity is approved.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        </div>
    </div>
<?php else: ?>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New token</h3></div>
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Name</label>
                    <input type="text" name="label" required maxlength="60" class="form-input" placeholder="Website OTP">
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Allowed IP addresses, optional</label>
                    <textarea name="allowed_ips" rows="3" class="form-input" placeholder="Leave empty to allow any server"></textarea>
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                    <input type="checkbox" name="legal_accept" value="1" required class="mt-1">
                    <span class="text-slate-300 text-sm leading-relaxed"><?= e(billing_use_declaration()) ?></span>
                </label>
                <button type="submit" name="create_token" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Create token</button>
            </form>
        </div>
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Your tokens</h3></div>
            <?php if ($tokens): ?>
                <div class="divide-y divide-slate-800">
                    <?php foreach ($tokens as $token): ?>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-white text-sm font-medium"><?= e($token['label']) ?></p>
                                    <p class="text-slate-500 text-xs mt-1"><?= e($token['token_prefix']) ?>… · <?= e(ucfirst($token['status'])) ?></p>
                                    <p class="text-slate-600 text-xs mt-1">Last used <?= $token['last_used_at'] ? e(sms_format_time($token['last_used_at'])) . ' Nepal time' : 'never' ?></p>
                                    <p class="text-slate-600 text-xs mt-1"><?= trim((string) $token['allowed_ips']) !== '' ? 'Only ' . e(str_replace(',', ', ', $token['allowed_ips'])) : 'Any address' ?></p>
                                </div>
                                <?php if ($token['status'] === 'active'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="token_id" value="<?= (int) $token['id'] ?>">
                                        <button type="submit" name="revoke_token" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="p-5 text-slate-500 text-sm">No tokens yet.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="dash-panel">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Call</h3></div>
    <div class="p-5 space-y-4 text-sm text-slate-300">
        <p>Send with POST. <code class="text-brand-300">auth_token</code>, <code class="text-brand-300">to</code>, and <code class="text-brand-300">text</code> can be form fields or JSON. <code class="text-brand-300">to</code> is one number or several separated by commas. At most 500 numbers. A token accepts 30 calls a minute.</p>
        <?php if ($route['choose_sender']): ?>
            <p>Add <code class="text-brand-300">from</code> with an approved sender name.</p>
        <?php endif; ?>
        <p class="text-slate-400 break-all">POST <?= e($sendUrl) ?></p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($sendUrl) ?> \
  -d auth_token=YOUR_TOKEN \
  -d to=98XXXXXXXX \
  -d text='Your code is 482193'</pre>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4"><?php
        $sample = '$args = http_build_query(array(' . "\n"
            . "    'auth_token' => 'YOUR_TOKEN',\n"
            . "    'to' => '98XXXXXXXX',\n"
            . "    'text' => 'Your code is 482193'\n"
            . "));\n"
            . '$ch = curl_init(' . var_export($sendUrl, true) . ");\n"
            . "curl_setopt(\$ch, CURLOPT_POST, true);\n"
            . "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$args);\n"
            . "curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n"
            . 'echo curl_exec($ch);';
        echo e($sample);
        ?></pre>
        <p>A sent message looks like this. <code class="text-brand-300">balance</code> is the SMS credit left on this account.</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">{ "error": false, "message": "1 SMS sent.", "data": { "count": 1, "credits_used": 1, "balance": 4999 } }</pre>
        <p>A bad token returns 401. A message that was not accepted returns 400 and the credits for those numbers come back. More than 30 calls in a minute returns 429.</p>
        <p class="text-slate-400 break-all">Credit check: POST <?= e($creditUrl) ?> with the same <code class="text-brand-300">auth_token</code>.</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">{ "error": false, "message": "SMS credit balance.", "data": { "balance": <?= (int) $apiBalance['sms'] ?> } }</pre>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
