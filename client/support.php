<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = get_client_id();
require_once __DIR__ . '/../includes/support-view.php';
$msg = '';
$err = '';
$ticketDraft = array('subject' => '', 'description' => '', 'priority' => 'medium', 'topic' => '', 'service_id' => 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    verify_csrf();
    $subject = substr(trim($_POST['subject'] ?? ''), 0, 160);
    $topic = isset($_POST['topic']) && is_string($_POST['topic']) ? $_POST['topic'] : '';
    $serviceLabel = '';
    $serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    if ($serviceId > 0) {
        $own = $conn->prepare('SELECT service_name FROM client_services WHERE id = ? AND client_id = ? LIMIT 1');
        $ownClient = (int) $cid;
        $own->bind_param('ii', $serviceId, $ownClient);
        $own->execute();
        $ownRow = db_fetch_assoc($own);
        $own->close();
        $serviceLabel = $ownRow ? $ownRow['service_name'] . ' (#' . $serviceId . ')' : '';
    }
    $typedDescription = substr(trim($_POST['description'] ?? ''), 0, 3800);
    $desc = $typedDescription === '' ? '' : substr(support_compose_description($topic, $serviceLabel, $typedDescription), 0, 4000);
    $priority = $_POST['priority'] ?? 'medium';
    $priorities = array('low', 'medium', 'high', 'urgent');
    if (!in_array($priority, $priorities, true)) {
        $priority = 'medium';
    }
    $ticketDraft = array('subject' => $subject, 'description' => $typedDescription, 'priority' => $priority, 'topic' => $topic, 'service_id' => $serviceId);
    if ($subject === '' || $desc === '') {
        $err = 'Write a short subject and tell us what happened.';
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
            $ticketDraft = array('subject' => '', 'description' => '', 'priority' => 'medium', 'topic' => '', 'service_id' => 0);
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
        $lookup = $conn->prepare('SELECT subject, status, client_followup FROM support_tickets WHERE id = ? AND client_id = ? LIMIT 1');
        $lookup->bind_param('ii', $ticketId, $cid);
        $lookup->execute();
        $ticketRow = db_fetch_assoc($lookup);
        $lookup->close();
        if (!$ticketRow) {
            $err = 'That ticket is not on this account.';
        } else {
            $open = 'open';
            $now = date('Y-m-d H:i:s');
            $earlier = trim((string) $ticketRow['client_followup']);
            $thread = ($earlier !== '' ? $earlier . "\n\n" : '') . '[' . date('Y-m-d H:i') . '] ' . $follow;
            if (strlen($thread) > 8000) {
                $thread = substr($thread, -8000);
            }
            $save = $conn->prepare('UPDATE support_tickets SET client_followup = ?, status = ?, updated_at = ? WHERE id = ? AND client_id = ?');
            $save->bind_param('sssii', $thread, $open, $now, $ticketId, $cid);
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
$myServices = array();
$svcStmt = $conn->prepare('SELECT id, service_name FROM client_services WHERE client_id = ? ORDER BY id DESC LIMIT 40');
$svcStmt->bind_param('i', $cid);
$svcStmt->execute();
$myServices = db_fetch_all($svcStmt) ?: array();
$svcStmt->close();
$counts = array('open' => 0, 'done' => 0);
foreach ($ticketRows as $countRow) {
    $counts[support_group((string) $countRow['status'])]++;
}
$show = isset($_GET['show']) && in_array($_GET['show'], array('open', 'done', 'all'), true) ? (string) $_GET['show'] : ($counts['open'] > 0 || !$ticketRows ? 'open' : 'all');
$shownRows = array_values(array_filter($ticketRows, function ($row) use ($show) {
    return $show === 'all' || support_group((string) $row['status']) === $show;
}));
?>
<div class="mb-8" id="support-top">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Support</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Open tickets stay in view. Older tickets are in the latest 40.' : 'Matches for “' . e($find) . '”.' ?> <a class="text-brand-400" href="manual.php#support">नेपाली चरण</a></p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div role="alert" class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php $startOnForm = ($err !== '' && isset($_POST['create_ticket'])) || isset($_GET['new']); ?>
<div x-data="{ tab: '<?= $startOnForm ? 'work' : 'list' ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">My tickets <span class="sup-count"><?= (int) $counts['open'] ?></span></button>
    <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">New ticket</button>
</div>

<div x-show="tab==='work'" x-cloak>
    <section class="sup-quick" aria-label="Quick answers">
        <h2>Maybe the answer is one tap away</h2>
        <ul>
            <?php foreach (support_quick_answers() as $quick): ?>
                <li><a href="<?= e($quick[2]) ?>"><b><?= e($quick[0]) ?></b><span><?= e($quick[1]) ?></span></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <div class="dash-panel mb-6">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Tell us what happened</h3></div>
        <form method="POST" action="" class="p-5 sup-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="kyc-grid">
                <div class="kyc-field"><label for="sup-topic" class="block">What is it about?</label>
                    <select id="sup-topic" name="topic"><option value="">Choose</option>
                        <?php foreach (support_topics() as $topicKey => $topicLabel): ?><option value="<?= e($topicKey) ?>" <?= $ticketDraft['topic'] === $topicKey ? 'selected' : '' ?>><?= e($topicLabel) ?></option><?php endforeach; ?>
                    </select></div>
                <?php if ($myServices): ?>
                <div class="kyc-field"><label for="sup-service" class="block">Which service? (if any)</label>
                    <select id="sup-service" name="service_id"><option value="0">None, or not sure</option>
                        <?php foreach ($myServices as $svcRow): ?><option value="<?= (int) $svcRow['id'] ?>" <?= (int) $ticketDraft['service_id'] === (int) $svcRow['id'] ? 'selected' : '' ?>><?= e($svcRow['service_name']) ?></option><?php endforeach; ?>
                    </select></div>
                <?php endif; ?>
            </div>
            <div class="kyc-field"><label for="sup-subject" class="block">Short title <span class="req-mark" aria-hidden="true">*</span></label>
                <input id="sup-subject" type="text" name="subject" required maxlength="160" placeholder="For example: SMS to Ncell numbers not arriving" value="<?= e($ticketDraft['subject']) ?>"></div>
            <div class="kyc-field"><label for="sup-desc" class="block">What happened? <span class="req-mark" aria-hidden="true">*</span></label>
                <textarea id="sup-desc" name="description" required rows="5" maxlength="3800" placeholder="What did you do, what did you expect, and what happened instead? Add numbers, dates or amounts if you have them."><?= e($ticketDraft['description']) ?></textarea>
                <small class="field-hint">The more we know now, the fewer questions we ask later.</small></div>
            <fieldset class="wal-step"><legend><b>?</b> How urgent is it?</legend>
                <div class="wal-methods">
                <?php foreach (support_urgency() as $urgencyKey => $urgency): ?>
                    <label class="wal-method"><input type="radio" name="priority" value="<?= e($urgencyKey) ?>" <?= $ticketDraft['priority'] === $urgencyKey ? 'checked' : '' ?>>
                        <span><b><?= e($urgency[0]) ?></b><small><?= e($urgency[1]) ?></small></span></label>
                <?php endforeach; ?>
                </div>
            </fieldset>
            <button type="submit" name="create_ticket" class="co-pay">Send to the team</button>
        </form>
    </div>
</div>

<div x-show="tab==='list'">
    <nav class="shop-tabs" aria-label="Show" style="position:static;border:0;padding:0 0 12px">
        <?php foreach (array('open' => 'Open', 'done' => 'Solved or closed', 'all' => 'All') as $key => $label): ?>
            <a href="support.php?show=<?= e($key) ?><?= $find !== '' ? '&amp;q=' . rawurlencode($find) : '' ?>" class="<?= $show === $key ? 'is-on' : '' ?>"<?= $show === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($key !== 'all'): ?><span><?= (int) $counts[$key] ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="hidden" name="show" value="<?= e($show) ?>">
        <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Search your tickets">
        <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
    </form>
    <div class="grid gap-4">
    <?php if ($shownRows): ?>
        <?php foreach ($shownRows as $t): $st = support_status_words((string) $t['status']); ?>
            <article class="sup-ticket is-<?= e($st[1]) ?>" id="t<?= (int) $t['id'] ?>">
                <header>
                    <div><h2><?= e($t['subject']) ?></h2><p>#<?= (int) $t['id'] ?> · opened <?= e(date('j M Y, g:i A', strtotime($t['created_at']))) ?><?= !empty($t['updated_at']) && $t['updated_at'] !== $t['created_at'] ? ' · updated ' . e(date('j M, g:i A', strtotime($t['updated_at']))) : '' ?></p></div>
                    <span class="svc-pill is-<?= e($st[1]) ?>"><?= e($st[0]) ?></span>
                </header>
                <p class="sup-hint"><?= e($st[2]) ?></p>
                <div class="sup-thread">
                    <div class="sup-msg is-you"><b>You</b><p><?= e($t['description']) ?></p></div>
                    <?php if (!empty($t['admin_reply'])): ?><div class="sup-msg is-team"><b>Support team</b><p><?= e($t['admin_reply']) ?></p></div><?php endif; ?>
                    <?php if (!empty($t['client_followup'])): ?><div class="sup-msg is-you"><b>Your follow-ups</b><p><?= e($t['client_followup']) ?></p></div><?php endif; ?>
                </div>
                <?php if ($t['status'] !== 'closed'): ?>
                    <form method="POST" class="sup-reply">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                        <label for="follow-<?= (int) $t['id'] ?>" class="block"><?= $t['status'] === 'resolved' ? 'Not fixed? Tell us here. This opens it again.' : 'Add something' ?></label>
                        <textarea id="follow-<?= (int) $t['id'] ?>" name="client_followup" rows="3" maxlength="2000" placeholder="What changed, or what you still need"></textarea>
                        <button type="submit" name="follow_ticket" value="1" class="kyc-btn is-camera" style="border:0">Send</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="shop-empty">
            <h2><?= $find !== '' ? 'No ticket matches that search' : ($show === 'done' ? 'Nothing solved or closed yet' : 'No open tickets') ?></h2>
            <p><?= $find === '' ? 'When something needs the team, open a ticket and follow the whole conversation here.' : 'Clear the search or choose All.' ?></p>
            <?php if ($find === ''): ?><button type="button" @click="tab='work'" class="shop-btn">Open a ticket</button><?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
