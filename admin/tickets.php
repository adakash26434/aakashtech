<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$find = admin_find_text(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');
$findBack = $find === '' ? 'tickets.php' : 'tickets.php?q=' . rawurlencode($find);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    verify_csrf();
    $id = (int)$_POST['ticket_id'];
    $reply = substr(trim($_POST['admin_reply'] ?? ''), 0, 4000);
    $status = $_POST['status'] ?? 'resolved';
    $statuses = array('open', 'in_progress', 'resolved', 'closed');
    if (!in_array($status, $statuses, true)) {
        $status = 'resolved';
    }
    $lookup = $conn->prepare('SELECT t.subject, c.email FROM support_tickets t JOIN client_users c ON c.id = t.client_id WHERE t.id = ? LIMIT 1');
    $ticketEmail = '';
    $ticketSubject = '';
    if ($lookup) {
        $lookup->bind_param('i', $id);
        $lookup->execute();
        $ticketRow = db_fetch_assoc($lookup);
        $lookup->close();
        if ($ticketRow) {
            $ticketEmail = (string) $ticketRow['email'];
            $ticketSubject = (string) $ticketRow['subject'];
        }
    }
    $stmt = $conn->prepare("UPDATE support_tickets SET admin_reply = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $reply, $status, $id);
    $stmt->execute();
    $stmt->close();
    if ($reply !== '' && $ticketEmail !== '') {
        billing_mail_person($conn, $ticketEmail, 'Reply on your support ticket', array(
            'There is a reply on: ' . $ticketSubject,
            $reply,
            'Open Support in the client panel to read it.'
        ));
    }
    header('Location: ' . $findBack);
    exit;
}

$ticketSql = 'SELECT t.*, c.name as client_name, c.email as client_email FROM support_tickets t JOIN client_users c ON t.client_id = c.id ';
$ticketRows = array();
if ($find !== '') {
    $like = '%' . $find . '%';
    $ticketStmt = $conn->prepare($ticketSql . 'WHERE t.subject LIKE ? OR t.description LIKE ? OR c.name LIKE ? OR c.email LIKE ? ORDER BY t.created_at DESC LIMIT 50');
    $ticketStmt->bind_param('ssss', $like, $like, $like, $like);
    $ticketStmt->execute();
    $ticketRows = db_fetch_all($ticketStmt);
    $ticketStmt->close();
} else {
    $ticketSeen = array();
    foreach (array(
        $ticketSql . "WHERE t.status IN ('open','in_progress') ORDER BY t.created_at DESC LIMIT 50",
        $ticketSql . 'ORDER BY t.created_at DESC LIMIT 100'
    ) as $ticketQuery) {
        $ticketResult = $conn->query($ticketQuery);
        if (!$ticketResult) {
            continue;
        }
        while ($ticketRow = $ticketResult->fetch_assoc()) {
            $ticketId = (int) $ticketRow['id'];
            if (isset($ticketSeen[$ticketId])) {
                continue;
            }
            $ticketSeen[$ticketId] = true;
            $ticketRows[] = $ticketRow;
        }
    }
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Support Tickets</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Open tickets stay in view. Older closed tickets are in the latest 100.' : 'Matches for “' . e($find) . '”.' ?></p>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Client, subject, or message">
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
                            <p class="text-slate-500 text-xs">From <?= e($t['client_name']) ?> · <?= e($t['client_email']) ?> · <?= date('M d, Y', strtotime($t['created_at'])) ?></p>
                        </div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full <?=
                            $t['status'] === 'open' ? 'bg-green-500/20 text-green-400' :
                            ($t['status'] === 'in_progress' ? 'bg-blue-500/20 text-blue-400' :
                            ($t['status'] === 'resolved' ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-600/20 text-slate-400'))
                        ?>"><?= str_replace('_', ' ', ucfirst($t['status'])) ?></span>
                    </div>
                    <p class="text-slate-300 text-sm mb-4"><?= e($t['description']) ?></p>

                    <?php if (!empty($t['admin_reply'])): ?>
                        <div class="p-3 bg-slate-800/50 rounded-xl mb-3">
                            <p class="text-slate-400 text-xs mb-1">Admin Reply:</p>
                            <p class="text-slate-300 text-sm"><?= e($t['admin_reply']) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="flex gap-2 flex-wrap">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                        <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
                        <input type="text" name="admin_reply" placeholder="Type a reply..." class="form-input flex-1 min-w-[200px]">
                        <select name="status" class="form-input w-auto">
                            <option value="open" <?= $t['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $t['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $t['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button type="submit" name="reply_ticket" class="px-5 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Reply</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dash-panel"><p class="p-12 text-center text-slate-500 text-sm"><?= $find === '' ? 'No support tickets yet.' : 'No ticket matches that search.' ?></p></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
