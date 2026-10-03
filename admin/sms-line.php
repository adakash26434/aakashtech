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
    $checked = sms_line_balance($conn);
    if (!empty($checked['ok'])) {
        $balanceNote = 'The line reports ' . number_format((int) $checked['balance']) . ' credits left. That figure is the account you buy from, not a client balance.';
    } else {
        $err = $checked['error'];
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
$tokenSet = billing_setting($conn, 'sms_line_token') !== '';
$endpoint = billing_setting($conn, 'sms_line_endpoint');
$senders = $conn->query('SELECT s.*, c.name AS client_name, c.email AS client_email FROM sms_sender_names s JOIN client_users c ON c.id = s.client_id ORDER BY s.id DESC LIMIT 50');
$recent = $conn->query('SELECT m.recipient, m.message_text, m.status, m.source, m.created_at, c.name AS client_name FROM sms_messages m JOIN client_users c ON c.id = m.client_id ORDER BY m.id DESC LIMIT 30');
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">SMS line</h1>
    <p class="text-slate-500 text-sm">Buy SMS in bulk, paste that account’s token here, and clients send from Aakash Technologies. They do not see which company the line is bought from.</p>
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

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Connection</h3></div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Where the SMS is bought</label>
                <select name="sms_line_provider" class="form-input">
                    <option value="" <?= $line['provider'] === '' ? 'selected' : '' ?>>Not connected</option>
                    <option value="aakash" <?= $line['provider'] === 'aakash' ? 'selected' : '' ?>>Aakash SMS account</option>
                    <option value="sparrow" <?= $line['provider'] === 'sparrow' ? 'selected' : '' ?>>Sparrow SMS account</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Token</label>
                <input type="password" name="sms_line_token" class="form-input" autocomplete="new-password" placeholder="<?= $tokenSet ? 'Saved. Leave blank to keep it.' : 'Paste the token' ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Sender name on that account</label>
                <input type="text" name="sms_line_sender" maxlength="11" value="<?= e($line['sender']) ?>" class="form-input" placeholder="AAKASH">
                <p class="text-slate-500 text-xs mt-1">Aakash SMS uses the name registered on that token. Sparrow also needs this as the default from name.</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Who chooses the name on the phone</label>
                <select name="sms_line_sender_mode" class="form-input">
                    <option value="fixed" <?= $line['sender_mode'] !== 'approved' ? 'selected' : '' ?>>Always the name above</option>
                    <option value="approved" <?= $line['sender_mode'] === 'approved' ? 'selected' : '' ?>>Sparrow only: a client name after you approve it</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Send URL, optional</label>
                <input type="text" name="sms_line_endpoint" value="<?= e($endpoint) ?>" class="form-input" placeholder="Leave empty for the standard address">
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
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Latest messages</h3></div>
    <?php if ($recent && $recent->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">When</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">To</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Message</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-400 text-xs"><?= e(date('M j, H:i', strtotime($row['created_at']))) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= e($row['client_name']) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= e($row['recipient']) ?></td>
                            <td class="px-4 py-3 text-slate-400 text-sm max-w-xs truncate"><?= e($row['message_text']) ?></td>
                            <td class="px-4 py-3 text-slate-300 text-sm"><?= e(ucfirst($row['status'])) ?> · <?= e($row['source']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No messages yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
