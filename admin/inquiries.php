<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$allowedFilters = array('new', 'read', 'replied', 'closed');
$filter = (isset($_REQUEST['status']) && in_array($_REQUEST['status'], $allowedFilters, true)) ? $_REQUEST['status'] : 'all';
$find = admin_find_text(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_inquiry_status'])) {
    verify_csrf();
    $id = (int) ($_POST['inquiry_id'] ?? 0);
    $status = isset($_POST['inquiry_status']) ? (string) $_POST['inquiry_status'] : '';
    if ($id > 0 && in_array($status, $allowedFilters, true)) {
        $stmt = $conn->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
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
        $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
        $types .= 'ssss';
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
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <?php if ($filter !== 'all'): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Name, email, phone, or message">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>

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
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <p class="text-white text-sm font-medium"><?= e($row['name']) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-300 text-sm"><?= e($row['email']) ?></p>
                                <p class="text-slate-500 text-xs"><?= e($row['phone']) ?></p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell"><span class="text-slate-300 text-sm"><?= e($row['service'] ?: 'General') ?></span></td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-500 text-sm"><?= date('M d, Y', strtotime($row['created_at'])) ?></span></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-medium rounded-full <?=
                                    $row['status'] === 'new' ? 'bg-green-500/20 text-green-400' :
                                    ($row['status'] === 'read' ? 'bg-blue-500/20 text-blue-400' :
                                    ($row['status'] === 'replied' ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-600/20 text-slate-400'))
                                ?>"><?= ucfirst($row['status']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" class="flex flex-wrap items-center gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="inquiry_id" value="<?= (int) $row['id'] ?>">
                                    <?php if ($filter !== 'all'): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
                                    <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
                                    <select name="inquiry_status" class="form-input w-auto text-sm" aria-label="Inquiry status">
                                        <?php foreach ($allowedFilters as $choice): ?>
                                            <option value="<?= e($choice) ?>" <?= $row['status'] === $choice ? 'selected' : '' ?>><?= e(ucfirst($choice)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="set_inquiry_status" value="1" class="text-brand-400 hover:text-brand-300 text-sm bg-transparent border-0 cursor-pointer p-0">Save</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-800/50 transition">
                            <td colspan="6" class="px-4 pb-4">
                                <p class="text-slate-300 text-sm whitespace-pre-wrap"><?= e($row['message']) ?></p>
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
