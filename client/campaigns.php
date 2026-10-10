<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = (int) get_client_id();
$msg = '';
$err = '';
$balances = billing_unit_balances($conn, $cid);
$voiceHeld = voice_reserved_count($conn, $cid);
$voiceFree = max(0, (int) $balances['voice_calls'] - $voiceHeld);
$kycReady = billing_kyc_approved($conn, $cid);
$audiences = billing_audiences();
$purposes = billing_purposes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_campaign'])) {
    verify_csrf();
    $cancelId = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : 0;
    $cancelled = sms_cancel_scheduled($conn, $cid, $cancelId);
    if ($cancelled === 'refunded') {
        $msg = 'Scheduled SMS cancelled. The held credits are back on this account.';
    } elseif ($cancelled === 'released') {
        $msg = 'Scheduled SMS cancelled. No credits were held.';
    } elseif (voice_cancel_job($conn, $cid, $cancelId)) {
        $msg = 'Voice job cancelled. No voice credits were used.';
    } else {
        $err = 'That job could not be cancelled.';
    }
}

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
    $language = isset($_POST['language']) ? (string) $_POST['language'] : '';
    $unitKind = $channel === 'voice' ? 'voice_calls' : 'sms';
    $numbers = $parsed['numbers'];
    $count = count($numbers);

    if ($channel === 'sms') {
        $err = 'Send SMS from the SMS dashboard. That send uses the credits on this account.';
    } elseif ($guardError !== '') {
        $err = $guardError;
    } elseif (billing_posted($_POST, 'legal_accept') !== '1') {
        $err = 'Accept the declaration before this can be saved.';
    } elseif ($name === '' || strlen($message) < 5 || !isset($audiences[$audience]) || !isset($purposes[$purpose])) {
        $err = 'Add a name, the exact text, who it is for, and why it is being sent.';
    } elseif (!$kycReady) {
        $err = 'Submit identity before a voice job can be saved.';
    } elseif ($channel === 'voice' && !in_array($language, array('Nepali', 'English'), true)) {
        $err = 'Choose Nepali or English.';
    } elseif ($channel === 'sms' && !preg_match('/^[A-Za-z0-9]{3,11}$/', $sender)) {
        $err = 'Enter a sender name of 3 to 11 letters or numbers.';
    } elseif (empty($parsed['ok'])) {
        $err = $parsed['error'];
    } elseif ($count < 1) {
        $err = 'Paste at least one 10-digit mobile number, one per line.';
    } elseif ($count > $voiceFree) {
        $err = 'This job needs ' . number_format($count) . ' voice credits. ' . number_format($voiceFree) . ' are free after waiting jobs.';
    } elseif (sms_prohibited_notice($message) !== '') {
        $err = sms_prohibited_notice($message);
    } else {
        $status = $scheduledValue ? 'scheduled' : 'draft';
        $list = implode("\n", $numbers);
        $declaration = billing_use_declaration();
        $scheduledValue = $scheduledValue === null ? '' : $scheduledValue;
        $stmt = $conn->prepare('INSERT INTO sms_campaigns (client_id, campaign_name, message_content, sender_id, recipients_count, status, scheduled_at, channel, audience, purpose, recipients_list, language, declaration_text) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssissssssss', $cid, $name, $message, $sender, $count, $status, $scheduledValue, $channel, $audience, $purpose, $list, $language, $declaration);
        if ($stmt->execute()) {
            billing_form_guard_clear($guardKey);
            $msg = 'Voice job saved. The team places the call, and the voice credits are used then.';
        } else {
            $err = 'The message could not be saved.';
        }
        $stmt->close();
    }
}

$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$campaignRows = array();
$campaignSeen = array();
if ($find !== '') {
    $like = '%' . $find . '%';
    $campaignStmt = $conn->prepare('SELECT * FROM sms_campaigns WHERE client_id = ? AND campaign_name LIKE ? ORDER BY created_at DESC LIMIT 50');
    $campaignStmt->bind_param('is', $cid, $like);
    $campaignStmt->execute();
    $campaignRows = db_fetch_all($campaignStmt);
    $campaignStmt->close();
} else {
    foreach (array(
        'SELECT * FROM sms_campaigns WHERE client_id = ? AND status IN (\'draft\',\'scheduled\') ORDER BY created_at DESC',
        'SELECT * FROM sms_campaigns WHERE client_id = ? ORDER BY created_at DESC LIMIT 80'
    ) as $campaignSql) {
        $campaignStmt = $conn->prepare($campaignSql);
        $campaignStmt->bind_param('i', $cid);
        $campaignStmt->execute();
        foreach (db_fetch_all($campaignStmt) as $campaignRow) {
            $campaignId = (int) $campaignRow['id'];
            if (isset($campaignSeen[$campaignId])) {
                continue;
            }
            $campaignSeen[$campaignId] = true;
            $campaignRows[] = $campaignRow;
        }
        $campaignStmt->close();
    }
}
$smsOutcomes = sms_outcome_counts($conn, $cid, $campaignRows);
$balances = billing_unit_balances($conn, $cid);
$voiceHeld = voice_reserved_count($conn, $cid);
$voiceFree = max(0, (int) $balances['voice_calls'] - $voiceHeld);
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Messages</h1>
        <p class="text-slate-500 text-sm"><?= $find === '' ? 'Waiting jobs stay in view. Older messages are in the latest 80.' : 'Matches for “' . e($find) . '”.' ?> SMS is sent from the <a href="sms-portal.php" class="text-brand-400">SMS dashboard</a>. A voice job is placed by the team. <a class="text-brand-400" href="manual.php#voice">नेपाली चरण</a></p>
    </div>
    <div class="flex gap-6 text-sm">
        <span class="text-slate-400">SMS credits: <strong class="text-white"><?= number_format($balances['sms']) ?></strong></span>
        <span class="text-slate-400">Voice credits: <strong class="text-white"><?= number_format($balances['voice_calls']) ?></strong><?php if ($voiceHeld > 0): ?> <span class="text-slate-500"><?= number_format($voiceHeld) ?> held by waiting jobs</span><?php endif; ?></span>
    </div>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Message name">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div x-data="{ tab: '<?= $err !== '' ? 'work' : 'list' ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">Jobs</button>
    <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">New voice call</button>
</div>
<div x-show="tab==='work'" x-cloak>
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">SMS</h3></div>
        <div class="p-5">
            <p class="text-slate-300 text-sm mb-4">Write the message, paste the numbers, and send them from the dashboard. Credits fall when the SMS is accepted. API tokens for OTP are on the same account.</p>
            <a href="sms-portal.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Open SMS dashboard</a>
        </div>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New voice call</h3></div>
        <?php if (!$kycReady): ?>
            <div class="p-5">
                <p class="text-slate-300 text-sm mb-4">Voice credits can be bought before identity is approved. A voice job stays closed until then.</p>
                <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
            </div>
        <?php else: ?>
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
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Language</label>
                <select name="language" required class="form-input">
                    <option value="">Select</option>
                    <option value="Nepali">Nepali</option>
                    <option value="English">English</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Exact script</label>
                <textarea name="message_content" required rows="4" class="form-input"></textarea>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Numbers, one per line</label>
                <textarea name="numbers" required rows="4" class="form-input" placeholder="One 10-digit number per line"></textarea>
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
            <button type="submit" name="create_campaign" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save voice job</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
<div x-show="tab==='list'">
<div class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Your jobs</h3></div>
    <?php if ($campaignRows): ?>
        <div class="divide-y divide-slate-800">
            <?php foreach ($campaignRows as $c): ?>
                <div class="p-4 flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium"><?= e($c['campaign_name']) ?> <span class="text-slate-500"><?= e(isset($c['channel']) && $c['channel'] === 'voice' ? 'Voice' : 'SMS') ?></span></p>
                        <p class="text-slate-500 text-xs mt-0.5 max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['message_content']) ?></p>
                        <?php if (isset($c['channel']) && $c['channel'] === 'voice' && !empty($c['recipients_list'])): ?>
                            <p class="text-slate-600 text-xs mt-1 max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['recipients_list']) ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-4 mt-2 text-xs text-slate-600">
                            <span><?= number_format($c['recipients_count']) ?> numbers</span>
                            <?php if (isset($c['channel']) && $c['channel'] === 'sms' && isset($smsOutcomes[(int) $c['id']])): ?>
                                <span><?= number_format($smsOutcomes[(int) $c['id']]['sent']) ?> sent</span>
                                <?php if ($smsOutcomes[(int) $c['id']]['failed'] > 0): ?><span><?= number_format($smsOutcomes[(int) $c['id']]['failed']) ?> failed</span><?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($c['language'])): ?><span><?= e($c['language']) ?></span><?php endif; ?>
                            <?php if (!empty($c['sender_id'])): ?><span>Sender: <?= e($c['sender_id']) ?></span><?php endif; ?>
                            <?php if (!empty($c['audience']) && isset($audiences[$c['audience']])): ?><span><?= e($audiences[$c['audience']]) ?></span><?php endif; ?>
                            <?php if (!empty($c['purpose']) && isset($purposes[$c['purpose']])): ?><span><?= e($purposes[$c['purpose']]) ?></span><?php endif; ?>
                            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
                            <?php if (isset($c['channel']) && $c['channel'] === 'sms' && $c['status'] !== 'scheduled'): ?>
                                <a href="sms-portal.php?send=<?= (int) $c['id'] ?>" class="text-brand-400">Use again</a>
                                <?php if (isset($smsOutcomes[(int) $c['id']]) && $smsOutcomes[(int) $c['id']]['failed'] > 0): ?>
                                    <a href="sms-portal.php?retry=<?= (int) $c['id'] ?>" class="text-brand-400">Retry failed</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-[10px] font-medium rounded-full flex-shrink-0 <?=
                        $c['status'] === 'sent' ? 'bg-green-500/20 text-green-400' :
                        ($c['status'] === 'scheduled' ? 'bg-yellow-500/20 text-yellow-400' :
                        ($c['status'] === 'sending' ? 'bg-blue-500/20 text-blue-400' :
                        ($c['status'] === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-slate-600/20 text-slate-400')))
                    ?>"><?php
                        if (isset($c['channel']) && $c['channel'] === 'voice' && $c['status'] === 'sent') {
                            echo 'Placed';
                        } elseif (isset($c['channel']) && $c['channel'] === 'voice' && $c['status'] === 'draft') {
                            echo 'Waiting';
                        } elseif ($c['status'] === 'cancelled') {
                            echo 'Cancelled';
                        } else {
                            echo e(ucfirst($c['status']));
                        }
                    ?></span>
                    <?php if ((isset($c['channel']) && $c['channel'] === 'sms' && $c['status'] === 'scheduled') || (isset($c['channel']) && $c['channel'] === 'voice' && ($c['status'] === 'draft' || $c['status'] === 'scheduled'))): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" name="cancel_campaign" class="text-slate-400 text-xs bg-transparent border-0 cursor-pointer" onclick="return confirm('<?= $c['channel'] === 'sms' ? 'Cancel this scheduled SMS?' : 'Cancel this voice campaign?' ?>')">Cancel</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm"><?= $find === '' ? 'No messages yet. SMS you send shows here. A voice job stays here until the team places the call.' : 'No message matches that name.' ?></p>
    <?php endif; ?>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
