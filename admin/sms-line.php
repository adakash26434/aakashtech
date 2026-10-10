<?php
require_once __DIR__ . '/../config.php';
require_admin();

$msg = '';
$err = '';
$balanceNote = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_line'])) {
    verify_csrf();
    $saved = sms_save_line($conn, $_POST);
    if ($saved === '') {
        billing_set_setting($conn, 'sms_refund_undelivered', !empty($_POST['refund_undelivered']) ? '1' : '0');
    }
    if ($saved === '') {
        $msg = 'SMS line saved. Clients send from this site and do not see these details.';
        $fresh = sms_vendor_stock($conn, true);
        if ($fresh['balance'] !== null && $fresh['error'] === '') {
            $msg .= ' This account has ' . number_format((int) $fresh['balance']) . ' SMS left.';
        } elseif ($fresh['label'] !== '') {
            $err = 'The key was saved, but the provider did not accept it: ' . ($fresh['error'] !== '' ? $fresh['error'] : 'balance could not be read') . ' Check the key and try again.';
        }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take_sms'])) {
    admin_deny_if_staff();
    verify_csrf();
    $reversed = sms_admin_reverse(
        $conn,
        isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0,
        isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0
    );
    if ($reversed['error'] === '') {
        $msg = $reversed['message'];
    } else {
        $err = $reversed['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_sms'])) {
    admin_deny_if_staff();
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
        if ($stmt) {
            $stmt->bind_param('ssi', $decision, $note, $senderId);
            $stmt->execute();
            $stmt->close();
            $msg = $decision === 'approved' ? 'Sender name approved.' : 'Sender name rejected.';
        } else {
            $err = 'That sender name could not be updated.';
        }
    }
}

// A test SMS or a balance check must not be sent again by a reload, so its result is shown after a redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $err === '' && $msg !== '' && (isset($_POST['grant_sms']) || isset($_POST['take_sms']) || isset($_POST['decision']) || isset($_POST['save_line']) || isset($_POST['test_line']) || isset($_POST['check_balance']))) {
    $doneTab = isset($_POST['decision']) ? 'names' : ((isset($_POST['save_line']) || isset($_POST['test_line']) || isset($_POST['check_balance'])) ? 'line' : 'credits');
    flash('sms_line_notice', $msg);
    header('Location: sms-line.php?tab=' . $doneTab);
    exit;
}
$lineNotice = flash('sms_line_notice');
if ($msg === '' && $lineNotice !== '') {
    $msg = (string) $lineNotice;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/sms-line-actions.php';
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

<?php
$smsTab = 'clients';
$smsTabs = array('clients', 'credits', 'line', 'names', 'history');
if (isset($_POST['save_line']) || isset($_POST['check_balance']) || isset($_POST['test_line'])) {
    $smsTab = 'line';
} elseif (isset($_POST['grant_sms']) || isset($_POST['take_sms'])) {
    $smsTab = 'credits';
} elseif (isset($_POST['decision'])) {
    $smsTab = 'names';
} elseif ($historyClient > 0 || $historyStatus !== '') {
    $smsTab = 'history';
} elseif (isset($_GET['tab']) && in_array($_GET['tab'], $smsTabs, true)) {
    $smsTab = (string) $_GET['tab'];
} elseif (!$line['connected']) {
    $smsTab = 'line';
}
$aakashRoute = 'v4';
if ($line['provider'] === 'aakash' && $endpoint !== '' && strpos($endpoint, '/sms/v4/') === false) {
    $aakashRoute = 'custom';
}
?>
<div x-data="{ tab: '<?= e($smsTab) ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" @click="tab='clients'" :class="tab==='clients' ? 'is-on' : ''">Clients</button>
    <button type="button" @click="tab='credits'" :class="tab==='credits' ? 'is-on' : ''">Credits</button>
    <button type="button" @click="tab='line'" :class="tab==='line' ? 'is-on' : ''">Line</button>
    <button type="button" @click="tab='names'" :class="tab==='names' ? 'is-on' : ''">Sender names<?= $pendingNames > 0 ? ' (' . (int) $pendingNames . ' waiting)' : '' ?></button>
    <button type="button" @click="tab='history'" :class="tab==='history' ? 'is-on' : ''">History</button>
</div>
<div x-show="tab==='clients'">
<div class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Who has used SMS</h3></div>
    <form method="GET" class="p-4 flex flex-wrap gap-2 border-b border-slate-800">
        <input type="hidden" name="tab" value="clients">
        <input type="search" name="q" value="<?= e($usageFind) ?>" class="form-input max-w-sm" placeholder="Client name, email, mobile, or message">
        <button type="submit" class="btn btn-primary">Find</button>
    </form>
    <?php if ($usage['rows']): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Used</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Left</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($usage['rows'] as $usageRow): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <a class="text-white text-sm hover:text-brand-300" href="client.php?id=<?= (int) $usageRow['id'] ?>"><?= e($usageRow['name']) ?></a>
                                <?php $usageEmail = filter_var($usageRow['email'], FILTER_VALIDATE_EMAIL) ? (string) $usageRow['email'] : ''; ?>
                                <?php if ($usageEmail !== ''): ?><a class="block text-slate-500 text-xs hover:text-brand-300" href="mailto:<?= e($usageEmail) ?>"><?= e($usageEmail) ?></a><?php else: ?><p class="text-slate-500 text-xs"><?= e($usageRow['email']) ?></p><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-white text-sm"><?= number_format((int) $usageRow['sms_used']) ?></td>
                            <td class="px-4 py-3 text-white text-sm"><?= number_format((int) $usageRow['sms_left']) ?><?php if ((int) $usageRow['sms_left'] < 100): ?> <span class="sms-state sms-state-<?= (int) $usageRow['sms_left'] < 1 ? 'failed' : 'wait' ?>"><?= (int) $usageRow['sms_left'] < 1 ? 'Empty' : 'Low' ?></span><?php endif; ?></td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap"><a class="text-brand-400" href="sms-line.php?tab=credits&grant=<?= (int) $usageRow['id'] ?>">Add credits</a> · <a class="text-brand-400" href="sms-line.php?tab=history&client=<?= (int) $usageRow['id'] ?>">See messages</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int) $usage['clients'] > count($usage['rows'])): ?>
            <p class="px-4 py-3 text-slate-500 text-xs">Showing 100 of <?= number_format((int) $usage['clients']) ?> clients. The totals above include everyone.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm"><?= $usageFind === '' ? 'No client has SMS credit or a sent message yet.' : 'No SMS client matches that search.' ?></p>
    <?php endif; ?>
</div>
</div>
<div x-show="tab==='credits'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add SMS credits</h3></div>
    <form method="POST" class="p-5 grid md:grid-cols-4 gap-3 items-end" x-data="{ credits: '' }">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_client">Client</label>
            <select id="grant_client" name="grant_client" class="form-input" required>
                <option value="">Choose</option>
                <?php if ($grantClients): ?>
                    <?php while ($grantClient = $grantClients->fetch_assoc()): ?>
                        <option value="<?= (int) $grantClient['id'] ?>" <?= $grantPick === (int) $grantClient['id'] ? 'selected' : '' ?>><?= e($grantClient['name']) ?> · <?= e($grantClient['email']) ?> · <?= number_format((int) $grantClient['sms_left']) ?> left</option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_credits">Credits</label>
            <input id="grant_credits" name="grant_credits" type="number" min="1" max="500000" required class="form-input" placeholder="1000" x-model="credits">
            <div class="flex flex-wrap gap-1 mt-2">
                <?php foreach (array(500, 1000, 5000, 10000) as $quickCredits): ?>
                    <button type="button" class="px-2.5 py-1 rounded-lg border border-slate-700 text-xs text-slate-300" @click="credits = '<?= (int) $quickCredits ?>'"><?= number_format($quickCredits) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="grant_note">Reason</label>
            <input id="grant_note" name="grant_note" type="text" maxlength="160" required class="form-input" placeholder="Paid at the office">
        </div>
        <button type="submit" name="grant_sms" class="btn btn-primary" onclick="var s=document.getElementById('grant_client'); var n=document.getElementById('grant_credits'); return !s.value || !n.value || confirm('Add ' + n.value + ' SMS credits to ' + s.options[s.selectedIndex].text.split(' · ')[0] + '?');">Add credits</button>
    </form>
</div>

<div class="dash-panel overflow-hidden mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Who received credits</h3></div>
    <?php if ($creditNotes && $creditNotes->num_rows > 0): ?>
        <div class="divide-y divide-slate-800">
            <?php while ($noteRow = $creditNotes->fetch_assoc()): ?>
                <div class="p-4 flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <p class="text-white text-sm"><a class="hover:text-brand-300" href="client.php?id=<?= (int) $noteRow['client_id'] ?>"><?= e($noteRow['name']) ?></a> <?php $noteEmail = filter_var($noteRow['email'], FILTER_VALIDATE_EMAIL) ? (string) $noteRow['email'] : ''; ?><?php if ($noteEmail !== ''): ?><a class="text-slate-500 hover:text-brand-300" href="mailto:<?= e($noteEmail) ?>"><?= e($noteEmail) ?></a><?php else: ?><span class="text-slate-500"><?= e($noteRow['email']) ?></span><?php endif; ?></p>
                        <p class="text-slate-400 text-xs mt-1"><?= e($noteRow['note']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-white text-sm"><?= number_format((int) $noteRow['credits']) ?> SMS<?= trim((string) $noteRow['reversed_at']) !== '' ? ' · Taken back' : '' ?> · <?= e(sms_format_time($noteRow['created_at'])) ?></p>
                        <?php if (trim((string) $noteRow['reversed_at']) === '' && (string) $noteRow['note'] !== 'Bought from the wallet'): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="client_id" value="<?= (int) $noteRow['client_id'] ?>">
                                <input type="hidden" name="note_id" value="<?= (int) $noteRow['id'] ?>">
                                <button type="submit" name="take_sms" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer" onclick="return confirm('Take back the unused part of these credits from this client?')">Take back unused</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No credit has been added yet. A wallet purchase, a renewal, or Add SMS credits shows here.</p>
    <?php endif; ?>
</div>
</div>
<div x-show="tab==='line'" x-cloak>
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Map the vendor API key</h3></div>
        <form method="POST" class="p-5 space-y-4" x-data="{ showKey: false, provider: '<?= e($line['provider']) ?>' }">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($line['connected']): ?>
                <p class="text-sm text-green-400">Connected to <?= e($vendorLabel) ?>. Saved API key ends in <?= e($tokenTail) ?>.</p>
            <?php else: ?>
                <p class="text-sm text-yellow-200">No API key is mapped yet. Clients cannot send until this is saved.</p>
            <?php endif; ?>
            <?php if ($aakashRoute === 'custom'): ?>
                <p class="text-sm text-yellow-200">A send address is saved, and it is not the v4 address. Clear Send URL and save again so Aakash SMS uses sms/v4/send-user.</p>
            <?php elseif ($line['provider'] === 'aakash' && $line['connected']): ?>
                <p class="text-sm text-slate-300">Sends go to sms/v4/send-user. The token is sent as the auth-token header. Balance is read from sms/v4/credit.</p>
            <?php endif; ?>
            <div class="rounded-xl border border-slate-700 p-4 text-sm text-slate-300">
                <p class="text-white font-medium mb-2">विक्रेताको स्क्रिनबाट यहीँ ल्याउनुहोस्</p>
                <ul class="space-y-1">
                    <li>Aakash SMS ड्यासबोर्डको <span class="text-white">auth token</span> → API key। v4 ले यो हेडरमा पठाउँछ।</li>
                    <li>Sparrow को <span class="text-white">token</span> → API key, र Sender ID → Sender name।</li>
                    <li x-show="provider==='aakash'">Aakash SMS v4 मा Sender name यो फारमबाट जाँदैन। फोनमा देखिने नाम त्यो टोकनमा दर्ता भएको नाम हो।</li>
                </ul>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_provider">1. Where the bulk SMS is bought</label>
                <select id="sms_line_provider" name="sms_line_provider" class="form-input" x-model="provider">
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
            <div x-show="provider==='sparrow'" x-cloak>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_sender">3. Sender name on the Sparrow account</label>
                <input id="sms_line_sender" type="text" name="sms_line_sender" maxlength="11" value="<?= e($line['sender']) ?>" class="form-input" placeholder="AAKASH" :disabled="provider!=='sparrow'">
                <p class="text-slate-500 text-xs mt-1">3 to 11 letters or numbers. This is the from name Sparrow requires.</p>
            </div>
            <div x-show="provider==='sparrow'" x-cloak>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_sender_mode">4. Who chooses the name on the phone</label>
                <select id="sms_line_sender_mode" name="sms_line_sender_mode" class="form-input" :disabled="provider!=='sparrow'">
                    <option value="fixed" <?= $line['sender_mode'] !== 'approved' ? 'selected' : '' ?>>Always the name above</option>
                    <option value="approved" <?= $line['sender_mode'] === 'approved' ? 'selected' : '' ?>>A client name after you approve it</option>
                </select>
            </div>
            <div x-show="provider==='aakash'">
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sms_line_endpoint">3. Send URL</label>
                <input id="sms_line_endpoint" type="text" name="sms_line_endpoint" value="<?= e($endpoint) ?>" class="form-input" placeholder="Leave empty for sms/v4/send-user" :disabled="provider!=='aakash'">
                <p class="text-slate-500 text-xs mt-1">Leave this empty. The token is sent in the auth-token header, and numbers go in the v4 to array. Balance uses sms/v4/credit.</p>
            </div>
            <div x-show="provider==='sparrow'" x-cloak>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">5. Send URL, optional</label>
                <input type="text" name="sms_line_endpoint" value="<?= e($endpoint) ?>" class="form-input" placeholder="Leave empty for the Sparrow address" :disabled="provider!=='sparrow'">
            </div>
            <label class="flex items-start gap-3 cursor-pointer" style="margin-top:4px">
                <input type="checkbox" name="refund_undelivered" value="1" <?= sms_refund_undelivered_on($conn) ? 'checked' : '' ?> class="mt-1">
                <span class="block text-slate-300 text-sm leading-relaxed"><b>Return credits when the phone network says a message was not delivered.</b> Clients are never charged for a message that did not arrive. Needs delivery reports from your provider (see the README). Messages the SMS line refuses are always returned.</span>
            </label>
            <button type="submit" name="save_line" class="btn btn-primary" onclick="var p=document.getElementById('sms_line_provider'); if (p && p.value==='') { return confirm('Disconnect the SMS line? Saving Not connected removes the stored bulk key, and every client send stops until you connect again.'); }">Save line</button>
        </form>
    </div>
    <div class="space-y-6">
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Line balance</h3></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <p class="text-slate-400 text-sm">Reads the credits left on the account you buy from. For Aakash SMS that is sms/v4/credit. Clients still spend only the credits they bought here.</p>
                <button type="submit" name="check_balance" class="btn btn-secondary">Check line balance</button>
            </form>
        </div>
        <div class="dash-panel">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Send a check</h3></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <p class="text-slate-400 text-sm">Sends “Aakash Technologies line check.” to one number. This spends credit on the bought account.</p>
                <input type="text" name="test_number" class="form-input" placeholder="98XXXXXXXX" inputmode="numeric">
                <button type="submit" name="test_line" class="btn btn-secondary" onclick="return confirm('Send a real test SMS? It uses one bulk credit.')">Send check</button>
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
</div>
<div x-show="tab==='names'" x-cloak>
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
                        <p class="text-slate-500 text-xs"><a class="hover:text-brand-300" href="client.php?id=<?= (int) $row['client_id'] ?>"><?= e($row['client_name']) ?></a> <span class="sms-state sms-state-<?= $row['status'] === 'approved' ? 'sent' : ($row['status'] === 'rejected' ? 'failed' : 'wait') ?>"><?= e(ucfirst((string) $row['status'])) ?></span></p>
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
</div>
<div x-show="tab==='history'" x-cloak>
<div class="dash-panel overflow-hidden">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sent message history</h3></div>
    <form method="GET" class="p-4 flex flex-wrap gap-2 border-b border-slate-800">
        <input type="hidden" name="tab" value="history">
        <input type="search" name="q" value="<?= e($usageFind) ?>" class="form-input max-w-sm" placeholder="Client, number, or message text">
        <?php if ($historyClient > 0): ?><input type="hidden" name="client" value="<?= (int) $historyClient ?>"><?php endif; ?>
        <select name="status" class="form-input max-w-[160px]">
            <option value="">Any status</option>
            <?php foreach (array('sent', 'failed', 'queued', 'scheduled') as $historyState): ?>
                <option value="<?= e($historyState) ?>" <?= $historyStatus === $historyState ? 'selected' : '' ?>><?= e(ucfirst($historyState)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Find</button>
        <?php if ($historyClient > 0 || $usageFind !== '' || $historyStatus !== ''): ?>
            <a href="sms-line.php?tab=history" class="px-4 py-2 text-slate-400 text-sm">Clear</a>
        <?php endif; ?>
    </form>
    <?php if ($history): ?>
        <div class="divide-y divide-slate-800">
            <?php foreach ($history as $row): ?>
                <article class="p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-white text-sm"><a class="hover:text-brand-300" href="client.php?id=<?= (int) $row['client_id'] ?>"><?= e($row['name']) ?></a> <?php $historyEmail = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? (string) $row['email'] : ''; ?><?php if ($historyEmail !== ''): ?><a class="text-slate-500 hover:text-brand-300" href="mailto:<?= e($historyEmail) ?>"><?= e($historyEmail) ?></a><?php else: ?><span class="text-slate-500"><?= e($row['email']) ?></span><?php endif; ?></p>
                        <p class="text-slate-500 text-xs"><?= e(sms_format_time($row['created_at'])) ?> · <?= (int) $row['parts'] ?> credit<?= (int) $row['parts'] === 1 ? '' : 's' ?> · <?= e($row['source'] === 'api' ? 'API' : 'Dashboard') ?> <span class="sms-state sms-state-<?= e(in_array($row['status'], array('sent', 'failed'), true) ? $row['status'] : 'wait') ?>"><?= e(ucfirst($row['status'])) ?></span></p>
                    </div>
                    <?php $historyPhone = preg_replace('/[^0-9+]/', '', (string) $row['recipient']); ?>
                    <p class="text-slate-300 text-sm mt-1">To <?php if ($historyPhone !== ''): ?><a class="hover:text-brand-300" href="tel:<?= e($historyPhone) ?>"><?= e($row['recipient']) ?></a><?php else: ?><?= e($row['recipient']) ?><?php endif; ?></p>
                    <p class="text-slate-700 text-sm mt-2 whitespace-pre-wrap"><?= e($row['message_text']) ?></p>
                    <?php if (isset($row['error_text']) && trim((string) $row['error_text']) !== ''): ?><p class="text-slate-500 text-xs mt-1"><?= e($row['error_text']) ?></p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($history) >= 80): ?>
            <p class="px-4 py-3 text-slate-500 text-xs">Showing the latest 80. Narrow the client or the status to see a shorter list.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="p-6 text-slate-500 text-sm">No message matches this view.</p>
    <?php endif; ?>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
