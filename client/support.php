<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = get_client_id();
$msg = '';
$err = '';
$ticketDraft = array('subject' => '', 'description' => '', 'priority' => 'medium');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    verify_csrf();
    $subject = substr(trim($_POST['subject'] ?? ''), 0, 160);
    $desc = substr(trim($_POST['description'] ?? ''), 0, 4000);
    $priority = $_POST['priority'] ?? 'medium';
    $priorities = array('low', 'medium', 'high', 'urgent');
    if (!in_array($priority, $priorities, true)) {
        $priority = 'medium';
    }
    $ticketDraft = array('subject' => $subject, 'description' => $desc, 'priority' => $priority);
    if ($subject === '' || $desc === '') {
        $err = 'Subject and description are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO support_tickets (client_id, subject, description, priority) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $cid, $subject, $desc, $priority);
        if ($stmt->execute()) {
            billing_mail_client_event($conn, (int) $cid, 'ticket-opened', array(
                'subject' => billing_notify_clip($subject, 160)
            ));
            billing_notify($conn, 'Support ticket: ' . $subject, array(
                'A client opened a support ticket.',
                'Subject: ' . $subject,
                'Priority: ' . $priority,
                'Message: ' . billing_notify_clip($desc, 800),
                'Client: ' . billing_notify_client_label($conn, $cid),
                'Open Admin → Support Tickets.'
            ));
            $msg = 'Support ticket created! We will respond shortly.';
            $ticketDraft = array('subject' => '', 'description' => '', 'priority' => 'medium');
        } else {
            $err = 'Failed to create ticket.';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_ticket'])) {
    verify_csrf();
    $ticketId = isset($_POST['ticket_id']) ? (int) $_POST['ticket_id'] : 0;
    $follow = billing_plain_block(isset($_POST['client_followup']) ? $_POST['client_followup'] : '', 2000);
    if ($ticketId < 1 || $follow === '') {
        $err = 'Write the follow-up before saving.';
    } else {
        $lookup = $conn->prepare('SELECT subject, status FROM support_tickets WHERE id = ? AND client_id = ? LIMIT 1');
        $lookup->bind_param('ii', $ticketId, $cid);
        $lookup->execute();
        $ticketRow = db_fetch_assoc($lookup);
        $lookup->close();
        if (!$ticketRow) {
            $err = 'That ticket is not on this account.';
        } else {
            $open = 'open';
            $now = date('Y-m-d H:i:s');
            $save = $conn->prepare('UPDATE support_tickets SET client_followup = ?, status = ?, updated_at = ? WHERE id = ? AND client_id = ?');
            $save->bind_param('sssii', $follow, $open, $now, $ticketId, $cid);
            if ($save->execute()) {
                billing_notify($conn, 'Follow-up on ticket: ' . $ticketRow['subject'], array(
                    'A client added a follow-up on a support ticket.',
                    'Subject: ' . $ticketRow['subject'],
                    'Follow-up: ' . billing_notify_clip($follow, 800),
                    'Client: ' . billing_notify_client_label($conn, $cid),
                    'Open Admin → Support Tickets.'
                ));
                $msg = 'Follow-up saved. The team can read it on this ticket.';
            } else {
                $err = 'The follow-up could not be saved.';
            }
            $save->close();
        }
    }
}

$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$cid = (int) $cid;
$ticketRows = array();
$ticketSeen = array();
if ($find !== '') {
    $like = '%' . $find . '%';
    $ticketStmt = $conn->prepare('SELECT * FROM support_tickets WHERE client_id = ? AND (subject LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT 50');
    $ticketStmt->bind_param('iss', $cid, $like, $like);
    $ticketStmt->execute();
    $ticketRows = db_fetch_all($ticketStmt);
    $ticketStmt->close();
} else {
    foreach (array(
        'SELECT * FROM support_tickets WHERE client_id = ? AND status IN (\'open\',\'in_progress\') ORDER BY created_at DESC',
        'SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC LIMIT 40'
    ) as $ticketSql) {
        $ticketStmt = $conn->prepare($ticketSql);
        $ticketStmt->bind_param('i', $cid);
        $ticketStmt->execute();
        foreach (db_fetch_all($ticketStmt) as $ticketRow) {
            $ticketId = (int) $ticketRow['id'];
            if (isset($ticketSeen[$ticketId])) {
                continue;
            }
            $ticketSeen[$ticketId] = true;
            $ticketRows[] = $ticketRow;
        }
        $ticketStmt->close();
    }
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Support</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Open tickets stay in view. Older tickets are in the latest 40.' : 'Matches for “' . e($find) . '”.' ?> <a class="text-brand-400" href="manual.php#support">नेपाली चरण</a></p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div x-data="{ tab: '<?= ($err !== '' && isset($_POST['create_ticket'])) ? 'work' : 'list' ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">Tickets</button>
    <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">New ticket</button>
</div>
<div x-show="tab==='work'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Open New Ticket</h3></div>
    <form method="POST" action="" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Subject *</label>
            <input type="text" name="subject" required class="form-input" placeholder="Brief description of your issue" value="<?= e($ticketDraft['subject']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Description *</label>
            <textarea name="description" required rows="4" class="form-input resize-none" placeholder="Describe your issue in detail..."><?= e($ticketDraft['description']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Priority</label>
            <select name="priority" class="form-input w-auto">
                <?php foreach (array('low', 'medium', 'high', 'urgent') as $priorityChoice): ?>
                    <option value="<?= e($priorityChoice) ?>" <?= $ticketDraft['priority'] === $priorityChoice ? 'selected' : '' ?>><?= e(ucfirst($priorityChoice)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="create_ticket" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit Ticket</button>
    </form>
</div>
</div>
<div x-show="tab==='list'">
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Subject or message">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<div class="grid gap-4">
    <?php if ($ticketRows): ?>
        <?php foreach ($ticketRows as $t): ?>
            <div class="dash-panel">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-heading font-semibold text-white text-base"><?= e($t['subject']) ?></h3>
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full <?=
                                    $t['priority'] === 'urgent' ? 'bg-red-500/20 text-red-400' :
                                    ($t['priority'] === 'high' ? 'bg-orange-500/20 text-orange-400' :
                                    ($t['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-blue-500/20 text-blue-400'))
                                ?>"><?= ucfirst($t['priority']) ?></span>
                            </div>
                            <p class="text-slate-500 text-xs"><?= date('M d, Y · h:i A', strtotime($t['created_at'])) ?></p>
                        </div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?=
                            $t['status'] === 'open' ? 'bg-green-500/20 text-green-400' :
                            ($t['status'] === 'in_progress' ? 'bg-blue-500/20 text-blue-400' :
                            ($t['status'] === 'resolved' ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-600/20 text-slate-400'))
                        ?>"><?= str_replace('_', ' ', ucfirst($t['status'])) ?></span>
                    </div>
                    <p class="text-slate-300 text-sm mb-3"><?= e($t['description']) ?></p>
                    <?php if (!empty($t['admin_reply'])): ?>
                        <div class="p-3 bg-brand-500/10 border border-brand-500/20 rounded-xl mb-3">
                            <p class="text-brand-400 text-xs font-medium mb-1">Support Team Reply:</p>
                            <p class="text-slate-300 text-sm whitespace-pre-wrap"><?= e($t['admin_reply']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($t['client_followup'])): ?>
                        <div class="p-3 bg-slate-800/50 rounded-xl mb-3">
                            <p class="text-slate-400 text-xs mb-1">Your follow-up</p>
                            <p class="text-slate-300 text-sm whitespace-pre-wrap"><?= e($t['client_followup']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($t['status'] !== 'closed'): ?>
                        <form method="POST" class="space-y-2">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                            <label class="block text-slate-400 text-xs font-medium" for="follow-<?= (int) $t['id'] ?>">Add a follow-up</label>
                            <textarea id="follow-<?= (int) $t['id'] ?>" name="client_followup" rows="3" maxlength="2000" class="form-input" placeholder="What changed, or what you still need"><?= e(isset($t['client_followup']) ? $t['client_followup'] : '') ?></textarea>
                            <button type="submit" name="follow_ticket" value="1" class="px-5 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save follow-up</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dash-panel"><p class="p-12 text-center text-slate-500 text-sm"><?= $find === '' ? 'No support tickets yet. Use the form above when you need the team.' : 'No ticket matches that search.' ?></p></div>
    <?php endif; ?>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
