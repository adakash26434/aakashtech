<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$msg = '';
$err = '';
$balanceNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_line'])) {
    verify_csrf();
    $saved = sms_save_line($conn, $_POST);
    if ($saved === '') {
        $msg = 'SMS line saved. Clients send from this site and do not see these details.';
    } else {
        $err = $saved;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_balance'])) {
    verify_csrf();
    $checked = sms_vendor_stock($conn, true);
    if ($checked['balance'] !== null && $checked['error'] === '') {
        $balanceNote = 'The bulk account has ' . number_format((int) $checked['balance']) . ' SMS left. That is the stock you bought, not a client balance.';
    } else {
        $err = $checked['error'] !== '' ? $checked['error'] : 'The bulk balance could not be read.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_line'])) {
    verify_csrf();
    $tested = sms_line_test($conn, isset($_POST['test_number']) ? $_POST['test_number'] : '');
    if ($tested === '') {
        $msg = 'Check message handed to the line. It uses that account, not a client’s credits.';
    } else {
        $err = $tested;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_sms'])) {
    verify_csrf();
    $granted = sms_admin_grant(
        $conn,
        isset($_POST['grant_client']) ? (int) $_POST['grant_client'] : 0,
        isset($_POST['grant_credits']) ? (int) $_POST['grant_credits'] : 0,
        isset($_POST['grant_note']) ? $_POST['grant_note'] : ''
    );
    if ($granted === '') {
        $msg = 'SMS credits added. The client can send them from this site.';
    } else {
        $err = $granted;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    verify_csrf();
    $senderId = isset($_POST['sender_id']) ? (int) $_POST['sender_id'] : 0;
    $decision = (isset($_POST['decision']) && $_POST['decision'] === 'approved') ? 'approved' : 'rejected';
    $note = billing_plain_line(isset($_POST['admin_note']) ? $_POST['admin_note'] : '', 180);
    if ($senderId > 0) {
        $stmt = $conn->prepare('UPDATE sms_sender_names SET status = ?, admin_note = ? WHERE id = ?');
        $stmt->bind_param('ssi', $decision, $note, $senderId);
        $stmt->execute();
        $stmt->close();
        $msg = $decision === 'approved' ? 'Sender name approved.' : 'Sender name rejected.';
    }
}

$line = sms_line($conn);
$storedKey = (string) sms_line_secret($conn)['token'];
$tokenSet = $storedKey !== '';
$tokenTail = $tokenSet ? substr($storedKey, -4) : '';
$endpoint = billing_setting($conn, 'sms_line_endpoint');
$vendorLabel = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
$senders = $conn->query('SELECT s.*, c.name AS client_name, c.email AS client_email FROM sms_sender_names s JOIN client_users c ON c.id = s.client_id ORDER BY s.id DESC LIMIT 50');
$usageFind = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$historyClient = isset($_GET['client']) ? (int) $_GET['client'] : 0;
$historyStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
$usage = sms_admin_usage($conn, $usageFind);
$history = sms_admin_history($conn, $historyClient, $usageFind, $historyStatus);
$vendorStock = sms_vendor_stock($conn, false);
$clientsHolding = sms_clients_holding($conn);
$grantClients = $conn->query('SELECT id, name, email FROM client_users ORDER BY name ASC LIMIT 200');
$creditNotes = $conn->query('SELECT n.credits, n.note, n.created_at, c.name, c.email FROM sms_credit_notes n JOIN client_users c ON c.id = n.client_id ORDER BY n.id DESC LIMIT 40');
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS line</h1>
    <p class="text-slate-500 text-sm">Paste the bulk API key once. Every client send, including their own API token, uses this line automatically. Nothing is pasted again in the client portal. <a class="text-brand-400" href="manual.php#sms-line">नेपाली चरण</a></p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($balanceNote): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($balanceNote) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="dash-stat-card">
        <div class="dash-stat-value"><?= $vendorStock['balance'] === null ? '—' : number_format((int) $vendorStock['balance']) ?></div>
        <div class="dash-stat-label">Bulk line left<?= $vendorStock['label'] !== '' ? ' · ' . e($vendorStock['label']) : '' ?></div>
    </div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $usage['clients']) ?></div><div class="dash-stat-label">SMS clients</div></div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $usage['used']) ?></div><div class="dash-stat-label">Used by clients</div></div>
    <div class="dash-stat-card"><div class="dash-stat-value"><?= number_format((int) $usage['left']) ?></div><div class="dash-stat-label">Still with clients</div></div>
</div>
<?php if ($vendorStock['balance'] !== null && (int) $vendorStock['balance'] < (int) $clientsHolding): ?>
    <div class="mb-4 p-4 rounded-2xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm">Clients still hold <?= number_format((int) $clientsHolding) ?> SMS. The bulk line has <?= number_format((int) $vendorStock['balance']) ?>. Buy more from <?= e($vendorStock['label'] !== '' ? $vendorStock['label'] : 'the vendor') ?> before those sends fail.</div>
<?php endif; ?>
<?php if ($vendorStock['error'] !== ''): ?>
    <p class="mb-4 text-sm text-yellow-200"><?= e($vendorStock['error']) ?><?= $vendorStock['balance'] !== null ? ' The last saved bulk figure is still shown.' : '' ?></p>
<?php elseif ($vendorStock['checked'] !== ''): ?>
    <p class="mb-4 text-xs text-slate-500">Bulk balance checked <?= e(sms_format_time($vendorStock['checked'])) ?>.</p>
<?php endif; ?>

<div class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Who has used SMS</h3></div>
    <form method="GET" class="p-4 flex flex-wrap gap-2 border-b border-slate-800">
        <input type="search" name="q" value="<?= e($usageFind) ?>" class="form-input max-w-sm" placeholder="Client name, email, number, or message">
        <?php if ($historyClient > 0): ?><input type="hidden" name="client" value="<?= (int) $historyClient ?>"><?php endif; ?>
        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
    </form>
    <?php if ($usage['rows']): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Used</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Left</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">History</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($usage['rows'] as $usageRow): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <p class="text-white text-sm"><?= e($usageRow['name']) ?></p>
                                <p class="text-slate-500 text-xs"><?= e($usageRow['email']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-white text-sm"><?= number_format((int) $usageRow['sms_used']) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= number_format((int) $usageRow['sms_left']) ?></td>
                            <td class="px-4 py-3"><a class="text-brand-400 text-sm" href="sms-line.php?client=<?= (int) $usageRow['id'] ?>">See messages</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm"><?= $usageFind === '' ? 'No client has SMS credit or a sent message yet.' : 'No SMS client matches that search.' ?></p>
    <?php endif; ?>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add SMS credits</h3></div>
    <form method="POST" class="p-5 grid md:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_client">Client</label>
            <select id="grant_client" name="grant_client" class="form-input" required>
                <option value="">Choose</option>
                <?php if ($grantClients): ?>
                    <?php while ($grantClient = $grantClients->fetch_assoc()): ?>
                        <option value="<?= (int) $grantClient['id'] ?>"><?= e($grantClient['name']) ?> · <?= e($grantClient['email']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_credits">Credits</label>
            <input id="grant_credits" name="grant_credits" type="number" min="1" max="500000" required class="form-input" placeholder="1000">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_note">Reason</label>
            <input id="grant_note" name="grant_note" type="text" maxlength="160" required class="form-input" placeholder="Paid at the office">
        </div>
        <button type="submit" name="grant_sms" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add credits</button>
    </form>
</div>

<div class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Who received credits</h3></div>
    <?php if ($creditNotes && $creditNotes->num_rows > 0): ?>
        <div class="divide-y divide-slate-800">
            <?php while ($noteRow = $creditNotes->fetch_assoc()): ?>
                <div class="p-4 flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <p class="text-white text-sm"><?= e($noteRow['name']) ?> <span class="text-slate-500"><?= e($noteRow['email']) ?></span></p>
                        <p class="text-slate-400 text-xs mt-1"><?= e($noteRow['note']) ?></p>
                    </div>
                    <p class="text-white text-sm"><?= number_format((int) $noteRow['credits']) ?> SMS · <?= e(sms_format_time($noteRow['created_at'])) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No credit has been added yet. A wallet purchase, a renewal, or Add SMS credits shows here.</p>
    <?php endif; ?>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Map the vendor API key</h3></div>
        <form method="POST" class="p-5 space-y-4" x-data="{ showKey: false }">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($line['connected']): ?>
                <p class="text-sm text-green-400">Connected to <?= e($vendorLabel) ?>. Saved API key ends in <?= e($tokenTail) ?>.</p>
            <?php else: ?>
                <p class="text-sm text-yellow-200">No API key is mapped yet. Clients cannot send until this is saved.</p>
            <?php endif; ?>
            <div class="rounded-xl border border-slate-700 p-4 text-sm text-slate-300">
                <p class="text-white font-medium mb-2">विक्रेताको स्क्रिनबाट यहीँ ल्याउनुहोस्</p>
                <ul class="space-y-1">
                    <li>Aakash SMS को <span class="text-white">auth token</span> → API key</li>
                    <li>Sparrow को <span class="text-white">token</span> → API key</li>
                    <li>Sender ID वा Identity → Sender name</li>
                </ul>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_provider">1. Where the bulk SMS is bought</label>
                <select id="sms_line_provider" name="sms_line_provider" class="form-input">
                    <option value="" <?= $line['provider'] === '' ? 'selected' : '' ?>>Not connected</option>
                    <option value="aakash" <?= $line['provider'] === 'aakash' ? 'selected' : '' ?>>Aakash SMS account</option>
                    <option value="sparrow" <?= $line['provider'] === 'sparrow' ? 'selected' : '' ?>>Sparrow SMS account</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_token">2. API key</label>
                <input id="sms_line_token" :type="showKey ? 'text' : 'password'" name="sms_line_token" class="form-input font-mono" autocomplete="off" spellcheck="false" placeholder="<?= $tokenSet ? 'Saved. Leave blank to keep it.' : 'Paste the API key' ?>">
                <label class="mt-2 flex items-center gap-2 text-xs text-slate-400">
                    <input type="checkbox" x-model="showKey">
                    <span>Show the key while pasting</span>
                </label>
                <p class="text-slate-500 text-xs mt-1">यो पूरा key फेरि देखिँदैन। सेभ भएपछि अन्तिम ४ अक्षर मात्र देखिन्छ। खाली छाडे पुरानै key रहन्छ। Not connected छानेर सेभ गरे key मेटिन्छ।</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_sender">3. Sender name on that account</label>
                <input id="sms_line_sender" type="text" name="sms_line_sender" maxlength="11" value="<?= e($line['sender']) ?>" class="form-input" placeholder="AAKASH">
                <p class="text-slate-500 text-xs mt-1">Aakash SMS uses the name registered on that token. Sparrow also needs this as the default from name. 3 to 11 letters or numbers.</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_sender_mode">4. Who chooses the name on the phone</label>
                <select id="sms_line_sender_mode" name="sms_line_sender_mode" class="form-input">
                    <option value="fixed" <?= $line['sender_mode'] !== 'approved' ? 'selected' : '' ?>>Always the name above</option>
                    <option value="approved" <?= $line['sender_mode'] === 'approved' ? 'selected' : '' ?>>Sparrow only: a client name after you approve it</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_endpoint">5. Send URL, optional</label>
                <input id="sms_line_endpoint" type="text" name="sms_line_endpoint" value="<?= e($endpoint) ?>" class="form-input" placeholder="Leave empty for the standard address">
            </div>
            <button type="submit" name="save_line" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save line</button>
        </form>
    </div>
    <div class="space-y-6">
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Line balance</h3></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <p class="text-slate-400 text-sm">Checks the credits left on the account you buy from, so you know when to top it up. Clients still spend only the credits they bought here.</p>
                <button type="submit" name="check_balance" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl transition">Check line balance</button>
            </form>
        </div>
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Send a check</h3></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <p class="text-slate-400 text-sm">Sends “Aakash Technologies line check.” to one number. This spends credit on the bought account.</p>
                <input type="text" name="test_number" class="form-input" placeholder="98XXXXXXXX" inputmode="numeric">
                <button type="submit" name="test_line" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl transition">Send check</button>
            </form>
        </div>
        <div class="dash-panel">
            <div class="p-5 text-sm text-slate-400">
                <p class="text-white font-medium mb-2">Scheduled SMS</p>
                <p>Run <code class="text-brand-300">php cron/sms-queue.php</code> every minute, or open <code class="text-brand-300">cron/sms-queue.php?key=YOUR_CRON_KEY</code>. Opening the client SMS page also sends anything that is already due.</p>
            </div>
        </div>
    </div>
</div>

<div class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sender name requests</h3></div>
    <?php if ($senders && $senders->num_rows > 0): ?>
        <div class="divide-y divide-slate-800">
            <?php while ($row = $senders->fetch_assoc()): ?>
                <form method="POST" class="p-4 flex flex-wrap items-center gap-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="sender_id" value="<?= (int) $row['id'] ?>">
                    <div class="min-w-[180px]">
                        <p class="text-white text-sm"><?= e($row['sender_name']) ?></p>
                        <p class="text-slate-500 text-xs"><?= e($row['client_name']) ?> · <?= e($row['status']) ?></p>
                    </div>
                    <input type="text" name="admin_note" value="<?= e($row['admin_note']) ?>" class="form-input max-w-xs" placeholder="Note">
                    <button type="submit" name="decision" value="approved" class="text-green-400 text-sm bg-transparent border-0 cursor-pointer">Approve</button>
                    <button type="submit" name="decision" value="rejected" class="text-red-400 text-sm bg-transparent border-0 cursor-pointer">Reject</button>
                </form>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No sender name requests. Clients only see this when Sparrow is set to approved names.</p>
    <?php endif; ?>
</div>

<div class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sent message history</h3></div>
    <form method="GET" class="p-4 flex flex-wrap gap-2 border-b border-slate-800">
        <input type="search" name="q" value="<?= e($usageFind) ?>" class="form-input max-w-sm" placeholder="Client, number, or message text">
        <?php if ($historyClient > 0): ?><input type="hidden" name="client" value="<?= (int) $historyClient ?>"><?php endif; ?>
        <select name="status" class="form-input max-w-[160px]">
            <option value="">Any status</option>
            <?php foreach (array('sent', 'failed', 'queued', 'scheduled') as $historyState): ?>
                <option value="<?= e($historyState) ?>" <?= $historyStatus === $historyState ? 'selected' : '' ?>><?= e(ucfirst($historyState)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
        <?php if ($historyClient > 0 || $usageFind !== '' || $historyStatus !== ''): ?>
            <a href="sms-line.php" class="px-4 py-2 text-slate-400 text-sm">Clear</a>
        <?php endif; ?>
    </form>
    <?php if ($history): ?>
        <div class="divide-y divide-slate-800">
            <?php foreach ($history as $row): ?>
                <article class="p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-white text-sm"><?= e($row['name']) ?> <span class="text-slate-500"><?= e($row['email']) ?></span></p>
                        <p class="text-slate-500 text-xs"><?= e(sms_format_time($row['created_at'])) ?> · <?= e(ucfirst($row['status'])) ?> · <?= (int) $row['parts'] ?> credit<?= (int) $row['parts'] === 1 ? '' : 's' ?> · <?= e($row['source']) ?></p>
                    </div>
                    <p class="text-slate-300 text-sm mt-1">To <?= e($row['recipient']) ?></p>
                    <p class="text-slate-200 text-sm mt-2 whitespace-pre-wrap"><?= e($row['message_text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No message matches this view.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
