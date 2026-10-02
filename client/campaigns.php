<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = (int) get_client_id();
$msg = '';
$err = '';
$balances = billing_unit_balances($conn, $cid);
$audiences = billing_audiences();
$purposes = billing_purposes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_campaign'])) {
    verify_csrf();
    $channel = isset($_POST['channel']) && $_POST['channel'] === 'voice' ? 'voice' : 'sms';
    $guardKey = $channel === 'voice' ? 'send-voice' : 'send-sms';
    $guardError = billing_form_guard_check($guardKey, $_POST);
    $name = billing_plain_line(isset($_POST['campaign_name']) ? $_POST['campaign_name'] : '', 120);
    $message = billing_plain_block(isset($_POST['message_content']) ? $_POST['message_content'] : '', 2000);
    $sender = billing_plain_line(isset($_POST['sender_id']) ? $_POST['sender_id'] : '', 11);
    $audience = isset($_POST['audience']) ? (string) $_POST['audience'] : '';
    $purpose = isset($_POST['purpose']) ? (string) $_POST['purpose'] : '';
    $parsed = billing_parse_numbers(isset($_POST['numbers']) ? $_POST['numbers'] : '');
    $scheduled = isset($_POST['scheduled_at']) ? trim((string) $_POST['scheduled_at']) : '';
    if ($scheduled !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $scheduled)) {
        $scheduled = '';
    }
    $scheduledValue = $scheduled !== '' ? str_replace('T', ' ', $scheduled) . ':00' : null;
    $unitKind = $channel === 'voice' ? 'voice_calls' : 'sms';
    $numbers = $parsed['numbers'];
    $count = count($numbers);

    if ($guardError !== '') {
        $err = $guardError;
    } elseif (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before this can be saved.';
    } elseif ($name === '' || strlen($message) < 5 || !isset($audiences[$audience]) || !isset($purposes[$purpose])) {
        $err = 'Add a name, the exact text, who it is for, and why it is being sent.';
    } elseif ($channel === 'sms' && !preg_match('/^[A-Za-z0-9]{3,11}$/', $sender)) {
        $err = 'Enter a sender name of 3 to 11 letters or numbers.';
    } elseif (empty($parsed['ok'])) {
        $err = $parsed['error'];
    } elseif ($count < 1) {
        $err = 'Paste at least one 10-digit mobile number, one per line.';
    } else {
        $status = $scheduledValue ? 'scheduled' : 'draft';
        $list = implode("\n", $numbers);
        $declaration = billing_use_declaration();
        $scheduledValue = $scheduledValue === null ? '' : $scheduledValue;
        $stmt = $conn->prepare('INSERT INTO sms_campaigns (client_id, campaign_name, message_content, sender_id, recipients_count, status, scheduled_at, channel, audience, purpose, recipients_list, declaration_text) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssisssssss', $cid, $name, $message, $sender, $count, $status, $scheduledValue, $channel, $audience, $purpose, $list, $declaration);
        if ($stmt->execute()) {
            billing_form_guard_clear($guardKey);
            $msg = 'Saved. This copy does not use your credits. Send from the SMS portal with the credits you already bought.';
        } else {
            $err = 'The message could not be saved.';
        }
        $stmt->close();
    }
}

$campaigns = $conn->query('SELECT * FROM sms_campaigns WHERE client_id = ' . $cid . ' ORDER BY created_at DESC');
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Messages</h1>
        <p class="text-slate-500 text-sm">Buy the credit in the shop. Send it from the <a href="sms-portal.php" class="text-brand-400">SMS portal</a>. A copy saved here does not reduce that credit.</p>
    </div>
    <div class="flex gap-6 text-sm">
        <span class="text-slate-400">SMS credits: <strong class="text-white"><?= number_format($balances['sms']) ?></strong></span>
        <span class="text-slate-400">Voice credits: <strong class="text-white"><?= number_format($balances['voice_calls']) ?></strong></span>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New SMS</h3></div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="channel" value="sms">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Name</label>
                <input type="text" name="campaign_name" required maxlength="120" placeholder="Annual general meeting notice" class="form-input">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Who it is for</label>
                    <select name="audience" required class="form-input">
                        <option value="">Select</option>
                        <?php foreach ($audiences as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Why</label>
                    <select name="purpose" required class="form-input">
                        <option value="">Select</option>
                        <?php foreach ($purposes as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Sender name</label>
                <input type="text" name="sender_id" required maxlength="11" placeholder="SAHAKARI" class="form-input">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Exact message</label>
                <textarea name="message_content" required rows="3" maxlength="480" class="form-input"></textarea>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Numbers, one per line</label>
                <textarea name="numbers" required rows="4" class="form-input" placeholder="98XXXXXXXX"></textarea>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Send time, optional</label>
                <input type="datetime-local" name="scheduled_at" class="form-input">
            </div>
            <?php
            $guardKey = 'send-sms';
            $declarationAccepted = isset($_POST['channel'], $_POST['legal_accept']) && $_POST['channel'] === 'sms' && $_POST['legal_accept'] === '1';
            require __DIR__ . '/../includes/use-declaration.php';
            ?>
            <button type="submit" name="create_campaign" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save SMS copy</button>
        </form>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New voice call</h3></div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="channel" value="voice">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Name</label>
                <input type="text" name="campaign_name" required maxlength="120" placeholder="Festival greeting" class="form-input">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Who it is for</label>
                    <select name="audience" required class="form-input">
                        <option value="">Select</option>
                        <?php foreach ($audiences as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Why</label>
                    <select name="purpose" required class="form-input">
                        <option value="">Select</option>
                        <?php foreach ($purposes as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Exact script</label>
                <textarea name="message_content" required rows="4" class="form-input"></textarea>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Numbers, one per line</label>
                <textarea name="numbers" required rows="4" class="form-input" placeholder="98XXXXXXXX"></textarea>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Call time, optional</label>
                <input type="datetime-local" name="scheduled_at" class="form-input">
            </div>
            <?php
            $guardKey = 'send-voice';
            $declarationAccepted = isset($_POST['channel'], $_POST['legal_accept']) && $_POST['channel'] === 'voice' && $_POST['legal_accept'] === '1';
            require __DIR__ . '/../includes/use-declaration.php';
            ?>
            <button type="submit" name="create_campaign" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save voice copy</button>
        </form>
    </div>
</div>

<div class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Your jobs</h3></div>
    <?php if ($campaigns && $campaigns->num_rows > 0): ?>
        <div class="divide-y divide-slate-800">
            <?php while ($c = $campaigns->fetch_assoc()): ?>
                <div class="p-4 flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium"><?= e($c['campaign_name']) ?> <span class="text-slate-500"><?= e(isset($c['channel']) && $c['channel'] === 'voice' ? 'Voice' : 'SMS') ?></span></p>
                        <p class="text-slate-500 text-xs mt-0.5 max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['message_content']) ?></p>
                        <?php if (!empty($c['recipients_list'])): ?>
                            <p class="text-slate-600 text-xs mt-1 max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['recipients_list']) ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-4 mt-2 text-xs text-slate-600">
                            <span><?= number_format($c['recipients_count']) ?> numbers</span>
                            <?php if (!empty($c['sender_id'])): ?><span>Sender: <?= e($c['sender_id']) ?></span><?php endif; ?>
                            <?php if (!empty($c['audience']) && isset($audiences[$c['audience']])): ?><span><?= e($audiences[$c['audience']]) ?></span><?php endif; ?>
                            <?php if (!empty($c['purpose']) && isset($purposes[$c['purpose']])): ?><span><?= e($purposes[$c['purpose']]) ?></span><?php endif; ?>
                            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-[10px] font-medium rounded-full flex-shrink-0 <?=
                        $c['status'] === 'sent' ? 'bg-green-500/20 text-green-400' :
                        ($c['status'] === 'scheduled' ? 'bg-yellow-500/20 text-yellow-400' :
                        ($c['status'] === 'sending' ? 'bg-blue-500/20 text-blue-400' :
                        ($c['status'] === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-slate-600/20 text-slate-400')))
                    ?>"><?= ucfirst($c['status']) ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm">No saved copies yet. Credits stay in the account until you send from the SMS portal.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
