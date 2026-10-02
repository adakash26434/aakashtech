<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    verify_csrf();
    $title = substr(trim($_POST['title'] ?? ''), 0, 120);
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $slug = substr(trim($slug, '-'), 0, 80);
    $desc = substr(trim($_POST['description'] ?? ''), 0, 500);
    $features = substr(trim($_POST['features'] ?? ''), 0, 300);
    $icon = strtolower(trim($_POST['icon'] ?? 'code'));
    $icon = preg_replace('/[^a-z0-9-]/', '', $icon);
    if ($icon === '') {
        $icon = 'code';
    }
    $icon = substr($icon, 0, 40);
    if ($title !== '' && $desc !== '') {
        $stmt = $conn->prepare("INSERT INTO services (title, slug, description, icon, features, sort_order) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $title, $slug, $desc, $icon, $features);
        $stmt->execute();
        $stmt->close();
        $msg = 'Service added successfully.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_service'])) {
    verify_csrf();
    $id = (int) ($_POST['service_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: services.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    verify_csrf();
    $id = (int) ($_POST['service_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM services WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: services.php');
    exit;
}

$services = $conn->query("SELECT * FROM services ORDER BY sort_order, id");
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Services</h1>
        <p class="text-slate-500 text-sm">Manage the service catalog. Checkout prices live under Billing.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="dash-panel mb-6">
    <div class="p-5 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h3 class="font-heading font-semibold text-white">Checkout prices</h3>
            <p class="text-slate-500 text-xs mt-1">The public cards and client shop use the prices saved in Billing.</p>
        </div>
        <a href="billing.php" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Open billing</a>
    </div>
</div>

<!-- Add Service Form -->
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add New Service</h3></div>
    <form method="POST" action="" class="p-5 grid sm:grid-cols-2 gap-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Title</label>
            <input type="text" name="title" required class="form-input" placeholder="e.g. Cloud Backup">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Icon (name)</label>
            <input type="text" name="icon" class="form-input" placeholder="e.g. cloud" value="code">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Description</label>
            <textarea name="description" required rows="2" class="form-input resize-none" placeholder="Short description..."></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-slate-400 text-xs font-medium mb-1.5">Features (comma separated)</label>
            <input type="text" name="features" class="form-input" placeholder="Feature 1,Feature 2,Feature 3">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" name="add_service" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Add Service</button>
        </div>
    </form>
</div>

<!-- Services List -->
<div class="dash-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-800 text-left">
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden md:table-cell">Description</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Active</th>
                    <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if ($services && $services->num_rows > 0): ?>
                    <?php while ($s = $services->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3"><p class="text-white text-sm font-medium"><?= e($s['title']) ?></p></td>
                            <td class="px-4 py-3 hidden md:table-cell"><p class="text-slate-500 text-sm truncate max-w-xs"><?= e($s['description']) ?></p></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $s['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-slate-600/20 text-slate-400' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="toggle_service" class="text-brand-400 hover:text-brand-300 text-sm bg-transparent border-0 cursor-pointer p-0"><?= $s['is_active'] ? 'Disable' : 'Enable' ?></button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Delete this service?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="delete_service" class="text-red-400 hover:text-red-300 text-sm bg-transparent border-0 cursor-pointer p-0">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500 text-sm">No services found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
