<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$allowedFilters = array('new', 'read', 'replied', 'closed');
$filter = (isset($_GET['status']) && in_array($_GET['status'], $allowedFilters, true)) ? $_GET['status'] : 'all';

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
    if ($filter === 'all') {
        $result = $conn->query('SELECT * FROM inquiries ORDER BY created_at DESC');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $inquiries[] = $row;
            }
        }
    } else {
        $stmt = $conn->prepare('SELECT * FROM inquiries WHERE status = ? ORDER BY created_at DESC');
        if ($stmt) {
            $stmt->bind_param('s', $filter);
            $stmt->execute();
            $inquiries = db_fetch_all($stmt);
            $stmt->close();
        }
    }
} catch (Throwable $exception) {
    error_log('Inquiries could not be listed.');
    $inquiries = array();
}
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Inquiries</h1>
        <p class="text-slate-500 text-sm">Manage contact form submissions</p>
    </div>
    <div class="flex gap-2">
        <a href="inquiries.php" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'all' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">All</a>
        <a href="?status=new" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'new' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">New</a>
        <a href="?status=read" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'read' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Read</a>
        <a href="?status=replied" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'replied' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Replied</a>
        <a href="?status=closed" class="px-4 py-2 text-sm rounded-lg <?= $filter === 'closed' ? 'bg-brand-500/20 text-brand-400 border border-brand-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">Closed</a>
    </div>
</div>

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
