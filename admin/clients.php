<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_client'])) {
    verify_csrf();
    $id = (int) ($_POST['client_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE client_users SET status = CASE WHEN status = 'active' THEN 'suspended' ELSE 'active' END WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: clients.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_authenticator'])) {
    verify_csrf();
    $id = (int) (isset($_POST['client_id']) ? $_POST['client_id'] : 0);
    if ($id > 0) {
        totp_clear($conn, 'client', $id);
        flash('client_notice', 'Authenticator reset. That client sets it up again on the next sign-in.');
    }
    header('Location: clients.php');
    exit;
}

$clientNotice = flash('client_notice');

$clients = $conn->query("SELECT * FROM client_users ORDER BY created_at DESC");
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Clients</h1>
    <p class="text-slate-500 text-sm">Manage registered clients. SMS credits are spent from the dashboard and API on this site.</p>
</div>

<?php if ($clientNotice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($clientNotice) ?></div>
<?php endif; ?>

<div class="dash-panel overflow-hidden">
    <?php if ($clients && $clients->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden md:table-cell">Company</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Phone</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Joined</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">SMS credits</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php while ($cl = $clients->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="portal-avatar-letter w-9 h-9 rounded-lg flex items-center justify-center font-heading font-bold text-white text-sm" style="background: <?= e($cl['avatar_color']) ?>"><?= strtoupper(substr($cl['name'], 0, 1)) ?></div>
                                    <div>
                                        <p class="text-white text-sm font-medium"><?= e($cl['name']) ?></p>
                                        <p class="text-slate-500 text-xs"><?= e($cl['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell"><span class="text-slate-300 text-sm"><?= e($cl['company'] ?: '—') ?></span></td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-300 text-sm"><?= e($cl['phone'] ?: '—') ?></span></td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-500 text-sm"><?= date('M d, Y', strtotime($cl['created_at'])) ?></span></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-medium rounded-full <?= $cl['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= ucfirst($cl['status']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php $clientUnits = billing_unit_balances($conn, (int) $cl['id']); ?>
                                <span class="text-white text-sm"><?= number_format($clientUnits['sms']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="client_id" value="<?= (int) $cl['id'] ?>">
                                    <button type="submit" name="toggle_client" class="text-sm bg-transparent border-0 cursor-pointer p-0 <?= $cl['status'] === 'active' ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' ?>"><?= $cl['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                                </form>
                                <?php if (isset($cl['totp_secret']) && $cl['totp_secret'] !== ''): ?>
                                    <form method="POST" class="mt-2" onsubmit="return confirm('Reset Google Authenticator for this client? They set it up again at the next sign-in.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="client_id" value="<?= (int) $cl['id'] ?>">
                                        <button type="submit" name="reset_authenticator" class="text-xs bg-transparent border-0 cursor-pointer p-0 text-slate-400 hover:text-white">Reset authenticator</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-slate-500 text-xs mt-2">Authenticator not set up</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm">No clients registered yet.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
