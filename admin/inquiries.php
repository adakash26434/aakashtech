<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$allowedFilters = array('new', 'read', 'replied', 'closed');
$filter = (isset($_REQUEST['status']) && in_array($_REQUEST['status'], $allowedFilters, true)) ? $_REQUEST['status'] : 'all';
$find = admin_find_text(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');
$openId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$msg = '';
$err = '';

function inquiry_back_url($id, $filter, $find)
{
    $parts = array();
    if ($id > 0) {
        $parts['id'] = (string) $id;
    }
    if ($filter !== 'all') {
        $parts['status'] = $filter;
    }
    if ($find !== '') {
        $parts['q'] = $find;
    }
    return 'inquiries.php' . ($parts ? '?' . http_build_query($parts) : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_inquiry_status'])) {
    verify_csrf();
    $id = isset($_POST['inquiry_id']) ? (int) $_POST['inquiry_id'] : 0;
    $status = isset($_POST['inquiry_status']) ? (string) $_POST['inquiry_status'] : '';
    $note = billing_plain_block(isset($_POST['admin_notes']) ? $_POST['admin_notes'] : '', 2000);
    if ($id < 1 || !in_array($status, $allowedFilters, true)) {
        $err = 'Choose a status before saving.';
    } else {
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare('UPDATE inquiries SET status = ?, admin_notes = ?, updated_at = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('sssi', $status, $note, $now, $id);
        }
        if ($stmt && $stmt->execute()) {
            $stmt->close();
            $openId = $id;
            $msg = 'Inquiry saved. The note stays on this page. It is not emailed to the visitor.';
        } else {
            if ($stmt) {
                $stmt->close();
            }
            $err = 'The inquiry could not be saved.';
        }
    }
}

$open = null;
if ($openId > 0) {
    $openStmt = $conn->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
    if ($openStmt) {
        $openStmt->bind_param('i', $openId);
        $openStmt->execute();
        $open = db_fetch_assoc($openStmt);
        $openStmt->close();
    }
}

$inquiries = array();
try {
    $where = array();
    $types = '';
    $params = array();
    if ($filter !== 'all') {
        $where[] = 'status = ?';
        $types .= 's';
        $params[] = $filter;
    }
    if ($find !== '') {
        $like = '%' . $find . '%';
        $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ? OR admin_notes LIKE ?)';
        $types .= 'sssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql = 'SELECT * FROM inquiries' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT ' . ($find === '' ? '200' : '50');
    $stmt = $conn->prepare($sql);
    if ($stmt && $types !== '') {
        $bind = array($types);
        foreach ($params as $key => $unused) {
            $bind[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind);
    }
    if ($stmt) {
        $stmt->execute();
        $inquiries = db_fetch_all($stmt);
        $stmt->close();
    }
} catch (Throwable $exception) {
    error_log('Inquiries could not be listed.');
    $inquiries = array();
}

function inquiry_status_class($status)
{
    if ($status === 'new') {
        return 'bg-green-500/20 text-green-400';
    }
    if ($status === 'read') {
        return 'bg-blue-500/20 text-blue-400';
    }
    if ($status === 'replied') {
        return 'bg-purple-500/20 text-purple-400';
    }
    return 'bg-slate-600/20 text-slate-400';
}
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Inquiries</h1>
        <p class="text-slate-500 text-sm"><?= $find === '' ? 'Latest 200 contact messages.' : 'Matches for “' . e($find) . '”.' ?></p>
    </div>
    <div class="flex gap-2">
        <?php $findQuery = $find === '' ? '' : '&q=' . rawurlencode($find); ?>
        <a href="inquiries.php<?= $find === '' ? '' : '?q=' . rawurlencode($find) ?>" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'all' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">All</a>
        <a href="?status=new<?= $findQuery ?>" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'new' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">New</a>
        <a href="?status=read<?= $findQuery ?>" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'read' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Read</a>
        <a href="?status=replied<?= $findQuery ?>" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'replied' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Replied</a>
        <a href="?status=closed<?= $findQuery ?>" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'closed' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Closed</a>
    </div>
</div>
<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <?php if ($filter !== 'all'): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Name, email, phone, message, or note">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>

<?php if ($open): ?>
    <?php
    $openEmail = filter_var($open['email'], FILTER_VALIDATE_EMAIL) ? (string) $open['email'] : '';
    $openPhone = preg_replace('/[^0-9+]/', '', (string) $open['phone']);
    ?>
    <div class="dash-panel mb-6">
        <div class="dash-panel-header">
            <h3 class="font-heading font-semibold text-white"><?= e($open['name']) ?></h3>
            <a href="<?= e(inquiry_back_url(0, $filter, $find)) ?>" class="text-slate-400 text-sm hover:text-white">Back to list</a>
        </div>
        <div class="p-5 space-y-4">
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <?php if ($openEmail !== ''): ?>
                    <a class="text-brand-400 hover:text-brand-300" href="mailto:<?= e($openEmail) ?>"><?= e($openEmail) ?></a>
                <?php else: ?>
                    <span class="text-slate-300"><?= e($open['email']) ?></span>
                <?php endif; ?>
                <?php if ($openPhone !== ''): ?>
                    <a class="text-brand-400 hover:text-brand-300" href="tel:<?= e($openPhone) ?>"><?= e($open['phone']) ?></a>
                <?php else: ?>
                    <span class="text-slate-300"><?= e($open['phone']) ?></span>
                <?php endif; ?>
                <span class="text-slate-400"><?= e($open['service'] ?: 'General') ?> · <?= e(date('M d, Y', strtotime($open['created_at']))) ?></span>
            </div>
            <p class="text-slate-300 text-sm whitespace-pre-wrap"><?= e($open['message']) ?></p>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="inquiry_id" value="<?= (int) $open['id'] ?>">
                <?php if ($filter !== 'all'): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
                <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="admin_notes">Office note</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" maxlength="2000" class="form-input" placeholder="What was said, the price quoted, or what to do next"><?= e(isset($open['admin_notes']) ? $open['admin_notes'] : '') ?></textarea>
                    <p class="text-slate-500 text-xs mt-1">This note is only for the office. The visitor does not receive it.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-slate-400 text-xs font-medium" for="inquiry_status">Status</label>
                    <select id="inquiry_status" name="inquiry_status" class="form-input w-auto text-sm">
                        <?php foreach ($allowedFilters as $choice): ?>
                            <option value="<?= e($choice) ?>" <?= $open['status'] === $choice ? 'selected' : '' ?>><?= e(ucfirst($choice)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="set_inquiry_status" value="1" class="px-5 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($openId > 0): ?>
    <p class="mb-4 text-sm text-yellow-200">That inquiry is not on this site.</p>
<?php endif; ?>

<div class="dash-panel overflow-hidden">
    <?php if ($inquiries): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden md:table-cell">Service</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Date</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($inquiries as $row): ?>
                        <?php
                        $rowEmail = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? (string) $row['email'] : '';
                        $rowPhone = preg_replace('/[^0-9+]/', '', (string) $row['phone']);
                        $rowUrl = inquiry_back_url((int) $row['id'], $filter, $find);
                        $rowNote = isset($row['admin_notes']) ? trim((string) $row['admin_notes']) : '';
                        ?>
                        <tr class="<?= (int) $row['id'] === $openId ? 'bg-slate-800/70' : 'hover:bg-slate-800/50' ?> transition">
                            <td class="px-4 py-3">
                                <a href="<?= e($rowUrl) ?>" class="text-white text-sm font-medium hover:text-brand-300"><?= e($row['name']) ?></a>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($rowEmail !== ''): ?>
                                    <a class="text-slate-300 text-sm hover:text-brand-300 block" href="mailto:<?= e($rowEmail) ?>"><?= e($rowEmail) ?></a>
                                <?php else: ?>
                                    <p class="text-slate-300 text-sm"><?= e($row['email']) ?></p>
                                <?php endif; ?>
                                <?php if ($rowPhone !== ''): ?>
                                    <a class="text-slate-500 text-xs hover:text-brand-300" href="tel:<?= e($rowPhone) ?>"><?= e($row['phone']) ?></a>
                                <?php else: ?>
                                    <p class="text-slate-500 text-xs"><?= e($row['phone']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell"><span class="text-slate-300 text-sm"><?= e($row['service'] ?: 'General') ?></span></td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-500 text-sm"><?= date('M d, Y', strtotime($row['created_at'])) ?></span></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= inquiry_status_class($row['status']) ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= e($rowUrl) ?>" class="text-brand-400 hover:text-brand-300 text-sm">Open</a>
                            </td>
                        </tr>
                        <tr class="<?= (int) $row['id'] === $openId ? 'bg-slate-800/70' : '' ?>">
                            <td colspan="6" class="px-4 pb-4">
                                <p class="text-slate-300 text-sm whitespace-pre-wrap"><?= e($row['message']) ?></p>
                                <?php if ($rowNote !== ''): ?>
                                    <p class="text-slate-500 text-xs mt-2 whitespace-pre-wrap">Note: <?= e($rowNote) ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm">No inquiries found.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
