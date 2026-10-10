<?php
ob_start();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$msg = '';
$err = '';
$plainToken = '';
$tokenDraft = array('label' => '', 'allowed_ips' => '');
if (!empty($_SESSION['sms_plain_token'])) {
    $plainToken = (string) $_SESSION['sms_plain_token'];
    unset($_SESSION['sms_plain_token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_token'])) {
    verify_csrf();
    $tokenDraft = array(
        'label' => isset($_POST['label']) ? (string) $_POST['label'] : '',
        'allowed_ips' => isset($_POST['allowed_ips']) ? (string) $_POST['allowed_ips'] : ''
    );
    if (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before a token can be created.';
    } else {
        if (function_exists('client_office_view') && client_office_view()) {
            $err = 'The client creates this token and enters their own authenticator code.';
        } else {
            $created = sms_create_token(
                $conn,
                $cid,
                $tokenDraft['label'],
                $tokenDraft['allowed_ips'],
                isset($_POST['authenticator_code']) ? $_POST['authenticator_code'] : ''
            );
            if (!empty($created['ok'])) {
                $_SESSION['sms_plain_token'] = $created['token'];
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                header('Location: sms-api.php');
                exit;
            }
            $err = $created['error'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_sms'])) {
    verify_csrf();
    $phoneStmt = $conn->prepare('SELECT phone FROM client_users WHERE id = ?');
    $phoneStmt->bind_param('i', $cid);
    $phoneStmt->execute();
    $phoneRow = db_fetch_assoc($phoneStmt);
    $phoneStmt->close();
    $ownMobile = $phoneRow ? auth_mobile_number((string) $phoneRow['phone']) : '';
    if (!preg_match('/^9[78]\d{8}$/', $ownMobile)) {
        $err = 'This account has no Nepal mobile for a test SMS.';
    } else {
        $tested = sms_send($conn, $cid, array(
            'name' => 'API test',
            'text' => 'Your test code is 482193. Do not share this code.',
            'numbers' => $ownMobile,
            'audience' => 'personal',
            'purpose' => 'otp',
            'source' => 'dashboard'
        ));
        if (!empty($tested['ok'])) {
            $msg = 'A test code was sent to ' . $ownMobile . '. One credit was used. Check SMS logs if it does not arrive.';
        } else {
            $err = $tested['error'];
        }
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
$reportUrl = ($origin !== '' ? $origin : '') . '/api/sms/report';
$route = sms_client_route($conn, $cid);
$apiBalance = billing_unit_balances($conn, $cid);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS API</h1>
    <p class="text-slate-500 text-sm">After you add SMS credit, create a token here for OTP or any other message from your own website. <?= number_format((int) $apiBalance['sms']) ?> credits left. The steps are on this page. <a class="text-brand-400" href="manual.php#api">नेपाली चरण</a></p>
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
        <p class="text-slate-400 text-xs mt-3">Paste it as auth_token. Three fields send an OTP: token, mobile, and text.</p>
        <pre id="sms-ready-call" class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4 mt-3">curl -X POST <?= e($sendUrl) ?> \
  -d auth_token=<?= e($plainToken) ?> \
  -d to=98XXXXXXXX \
  -d text='Your code is 482193'</pre>
        <button type="button" class="mt-3 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-lg" onclick="navigator.clipboard.writeText(document.getElementById('sms-ready-call').textContent)">Copy this call</button>
    </div>
<?php endif; ?>

<?php $canToken = $kycReady || (int) $apiBalance['sms'] > 0; ?>
<?php if (!$kycReady && $canToken): ?>
    <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">This token spends the SMS credit you added. More than 100 SMS in total needs KYC. <a class="text-brand-300" href="kyc.php">Update KYC</a></div>
<?php endif; ?>
<?php if (!$canToken): ?>
    <div class="dash-panel mb-6">
        <div class="p-6">
            <p class="text-slate-300 text-sm mb-4">Buy SMS credit first. The token sends from that credit, for an OTP or any other message on your website.</p>
            <a href="shop.php?service=bulk-sms" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Buy SMS credit</a>
        </div>
    </div>
<?php else: ?>
    <div x-data="{ tab: '<?= ($err !== '' && isset($_POST['create_token'])) ? 'work' : 'list' ?>' }">
    <div class="portal-tabs" role="tablist">
        <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">Your tokens</button>
        <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">New token</button>
    </div>
    <div x-show="tab==='work'" x-cloak>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New token</h3></div>
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Name</label>
                    <input type="text" name="label" required maxlength="60" class="form-input" placeholder="Website OTP" value="<?= e($tokenDraft['label']) ?>">
                </div>
                <details class="text-sm text-slate-400">
                    <summary class="cursor-pointer">Optional: allow only one server</summary>
                    <textarea name="allowed_ips" rows="2" class="form-input mt-2" placeholder="Leave empty. Any server can use the token."><?= e($tokenDraft['allowed_ips']) ?></textarea>
                </details>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="authenticator_code">Authenticator code</label>
                    <input id="authenticator_code" name="authenticator_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="form-input" placeholder="6-digit code">
                    <p class="text-slate-500 text-xs mt-1">Enter the current code from Google Authenticator. The same code cannot be used again.</p>
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                    <input type="checkbox" name="legal_accept" value="1" required class="mt-1">
                    <span class="text-slate-300 text-sm leading-relaxed"><?= e(billing_use_declaration()) ?></span>
                </label>
                <button type="submit" name="create_token" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Create token</button>
            </form>
        </div>
    </div>
    </div>
    <div x-show="tab==='list'">
        <div class="dash-panel mb-6">
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
                                        <button type="submit" name="revoke_token" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer" onclick="return confirm('Revoke this token? Any system using it will stop sending right away.')">Revoke</button>
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
    </div>
<?php endif; ?>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Send SMS</h3></div>
    <div class="p-5 space-y-4 text-sm text-slate-300">
        <p>Put this token in a website, app, or office software. Three fields send the SMS. Send it as POST only: GET is refused, and anything in the web address is ignored, so keep the token in the body or the header. A JSON body works too. Add an optional <code class="text-brand-300">client_ref</code> (letters, numbers, dot, dash or underscore, up to 64 characters) when you might retry: the same ref returns the first result and sends nothing or charges nothing again. <a class="text-brand-400" href="manual.php#api">नेपाली चरण</a></p>
        <p class="text-slate-400 break-all">URL <?= e($sendUrl) ?></p>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs uppercase">
                        <th class="py-2 pr-4">Field</th>
                        <th class="py-2 pr-4">Type</th>
                        <th class="py-2">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <tr>
                        <td class="py-2 pr-4 text-white">auth_token</td>
                        <td class="py-2 pr-4">string</td>
                        <td class="py-2">Token from this page. The header <code class="text-brand-300">auth-token</code> or <code class="text-brand-300">Authorization: Bearer</code> also works.</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-white">to</td>
                        <td class="py-2 pr-4">string or list</td>
                        <td class="py-2">10-digit Nepal mobiles, separated by commas, or a list. One message can go to 500 numbers. A number that is not valid is skipped. The others still send.</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 text-white">text</td>
                        <td class="py-2 pr-4">string or list</td>
                        <td class="py-2">The message. One text goes to every number. A list with one text per number sends a different message, up to 20.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-white">cURL</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($sendUrl) ?> \
  -d auth_token=YOUR_TOKEN \
  -d to=9800000001,9800000002 \
  -d text='Your code is 482193'</pre>
        <p class="text-white">PHP</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">$args = http_build_query(array(
    'auth_token' => 'YOUR_TOKEN',
    'to' => '9800000001,9800000002',
    'text' => 'Your code is 482193'
));
$ch = curl_init(<?= json_encode($sendUrl) ?>);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);</pre>
        <p class="text-white">Python</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">import requests
r = requests.post(
    <?= json_encode($sendUrl) ?>,
    data={
        'auth_token': 'YOUR_TOKEN',
        'to': '9800000001,9800000002',
        'text': 'Your code is 482193'
    })
print(r.status_code)
print(r.json())</pre>
        <p class="text-white">C#</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">using (var client = new WebClient()) {
    var values = new NameValueCollection();
    values["auth_token"] = "YOUR_TOKEN";
    values["to"] = "9800000001,9800000002";
    values["text"] = "Your code is 482193";
    var response = client.UploadValues(<?= json_encode($sendUrl) ?>, "POST", values);
    var responseString = Encoding.UTF8.GetString(response);
}</pre>
        <p class="text-white">Different message for each number</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($sendUrl) ?> \
  -H 'Content-Type: application/json' \
  -H 'auth-token: YOUR_TOKEN' \
  -d '{"to":["9800000001","9800000002"],"text":["Your code is 482193","Your code is 771520"]}'</pre>
        <p>Success</p>
        <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">{
  "error": false,
  "message": "1 SMS sent.",
  "data": {
    "count": 1,
    "failed": 1,
    "credits_used": 1,
    "balance": 4999,
    "available_credit": 4999,
    "valid": [{ "id": 12, "mobile": "9800000001", "text": "Your code is 482193", "credit": 1, "status": "sent" }],
    "invalid": [{ "mobile": "988585584", "text": "Your code is 482193", "credit": 0, "status": "invalid" }]
  }
}</pre>
        <p>Failure</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>400 — <code class="text-brand-300">The auth token field is required.</code> The same shape is used when <code class="text-brand-300">to</code> or <code class="text-brand-300">text</code> is missing.</li>
            <li>401 — <code class="text-brand-300">The provided auth token is not valid.</code></li>
            <li>400 — <code class="text-brand-300">Not enough balance.</code> Nothing is sent.</li>
            <li>400 — <code class="text-brand-300">No valid recipients.</code> The <code class="text-brand-300">invalid</code> list shows the numbers.</li>
            <li>429 — more than 30 calls in a minute. 405 — use POST or GET.</li>
        </ul>
        <?php if ((int) $apiBalance['sms'] > 0): ?>
        <form method="POST" class="pt-2">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" name="test_sms" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl">Send a test code to my mobile</button>
            <p class="text-slate-500 text-xs mt-2">Uses 1 credit and sends to the mobile on this account. A website OTP later shows in <a class="text-brand-400" href="sms-logs.php?source=api">SMS logs under API / OTP</a>.</p>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Credit</h3></div>
        <div class="p-5 space-y-3 text-sm text-slate-300">
            <p>Check the credits left before a send. POST or GET. Same <code class="text-brand-300">auth_token</code>.</p>
            <p class="text-slate-400 break-all"><?= e($creditUrl) ?></p>
            <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($creditUrl) ?> \
  -d auth_token=YOUR_TOKEN</pre>
            <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">{
  "error": false,
  "message": "SMS credit balance.",
  "data": {
    "available_credit": 1200,
    "balance": 1200,
    "total_sms_sent": 40,
    "last_sent": "3 Oct 2026, 2:10 PM"
  }
}</pre>
            <p>You have <?= number_format((int) $apiBalance['sms']) ?> credits now. <code class="text-brand-300">total_sms_sent</code> is how many messages this account has already sent.</p>
        </div>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Report</h3></div>
        <div class="p-5 space-y-3 text-sm text-slate-300">
            <p>Pull a date range into your own software. Dates are Nepal dates, <code class="text-brand-300">Y-m-d</code>. One call covers up to 62 days, 100 rows per page.</p>
            <p class="text-slate-400 break-all"><?= e($reportUrl) ?></p>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-slate-800">
                        <tr><td class="py-2 pr-4 text-white">auth_token</td><td class="py-2">Token from this page</td></tr>
                        <tr><td class="py-2 pr-4 text-white">start_date</td><td class="py-2">2026-10-01</td></tr>
                        <tr><td class="py-2 pr-4 text-white">end_date</td><td class="py-2">2026-10-03</td></tr>
                        <tr><td class="py-2 pr-4 text-white">page</td><td class="py-2">Optional. Starts at 1. <code class="text-brand-300">has_more</code> says when to ask for the next page.</td></tr>
                    </tbody>
                </table>
            </div>
            <pre class="overflow-x-auto text-xs text-slate-300 bg-slate-900/70 rounded-xl p-4">curl -X POST <?= e($reportUrl) ?> \
  -d auth_token=YOUR_TOKEN \
  -d start_date=2026-10-01 \
  -d end_date=2026-10-03 \
  -d page=1</pre>
            <p>Each row has <code class="text-brand-300">mobile</code>, <code class="text-brand-300">text</code>, <code class="text-brand-300">credit</code>, <code class="text-brand-300">status</code>, and <code class="text-brand-300">at</code>. Add <code class="text-brand-300">source=api</code> to see only website and OTP sends.</p>
        </div>
    </div>
</div>
<style>
.sms-code-wrap { position: relative; }
.sms-code-copy { position: absolute; top: 8px; right: 8px; padding: 3px 10px; border: 1px solid #cddbd4; border-radius: 8px; background: #fff; color: #075e54; font-size: 12px; font-weight: 700; cursor: pointer; }
</style>
<script>
document.querySelectorAll('pre[class~="bg-slate-900/70"]').forEach(function (block) {
    if (block.id === 'sms-ready-call') return;
    var wrap = document.createElement('div');
    wrap.className = 'sms-code-wrap';
    block.parentNode.insertBefore(wrap, block);
    wrap.appendChild(block);
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'sms-code-copy';
    button.textContent = 'Copy';
    button.addEventListener('click', function () {
        var label = function (text) {
            button.textContent = text;
            setTimeout(function () { button.textContent = 'Copy'; }, 1500);
        };
        if (!navigator.clipboard) { label('Copy not supported'); return; }
        navigator.clipboard.writeText(block.textContent).then(function () {
            label('Copied');
        }, function () {
            label('Copy failed');
        });
    });
    wrap.appendChild(button);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
