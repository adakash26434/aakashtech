<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$back = 'clients.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    verify_csrf();
    if (isset($_POST['toggle_client'])) {
        $stmt = $conn->prepare("UPDATE client_users SET status = CASE WHEN status = 'active' THEN 'suspended' ELSE 'active' END WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        flash('client_notice', 'Account status updated.');
    } elseif (isset($_POST['reset_authenticator'])) {
        totp_clear($conn, 'client', $id);
        flash('client_notice', 'Authenticator reset. The client sets it up again on the next sign-in.');
    } elseif (isset($_POST['set_contact'])) {
        $contactError = billing_admin_set_contact(
            $conn,
            $id,
            isset($_POST['contact_email']) ? $_POST['contact_email'] : '',
            isset($_POST['contact_phone']) ? $_POST['contact_phone'] : ''
        );
        if ($contactError === '') {
            flash('client_notice', 'Email and mobile saved.');
        } else {
            flash('client_error', $contactError);
        }
    }
    header('Location: client.php?id=' . $id);
    exit;
}

$client = null;
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM client_users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $client = db_fetch_assoc($stmt);
    $stmt->close();
}

$services = array();
$balance = '0.00';
$figures = array('used' => 0, 'left' => 0);
if ($client) {
    $balance = billing_balance($conn, $id);
    $one = sms_admin_client_figures($conn, array($id));
    if (isset($one[$id])) {
        $figures = $one[$id];
    }
    $svc = $conn->prepare('SELECT service_name, status, detail_label, price, next_renewal FROM client_services WHERE client_id = ? ORDER BY id DESC LIMIT 40');
    $svc->bind_param('i', $id);
    $svc->execute();
    $services = db_fetch_all($svc);
    $svc->close();
}

$notice = flash('client_notice');
$problem = flash('client_error');
?>
<div class="mb-8">
    <a href="<?= e($back) ?>" class="text-brand-400 text-sm">← Clients</a>
    <h1 class="font-heading font-bold text-white text-2xl mt-2"><?= $client ? e($client['name']) : 'Client' ?></h1>
</div>

<?php if ($notice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($problem !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($problem) ?></div>
<?php endif; ?>

<?php if (!$client): ?>
    <div class="dash-panel"><p class="p-8 text-slate-500 text-sm">That client was not found.</p></div>
<?php else: ?>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Account</h2></div>
            <dl class="p-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="text-white text-right"><?= e($client['email']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Mobile</dt><dd class="text-white text-right"><?= e($client['phone']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Company</dt><dd class="text-white text-right"><?= e(trim((string) $client['company']) !== '' ? $client['company'] : '—') ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Address</dt><dd class="text-white text-right"><?= e(trim((string) $client['address']) !== '' ? $client['address'] : '—') ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd class="text-white text-right"><?= e(ucfirst((string) $client['status'])) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Wallet</dt><dd class="text-white text-right">NPR <?= e(number_format((float) $balance, 2)) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">SMS used / left</dt><dd class="text-white text-right"><?= number_format((int) $figures['used']) ?> / <?= number_format((int) $figures['left']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Joined</dt><dd class="text-white text-right"><?= e(date('M j, Y', strtotime($client['created_at']))) ?></dd></div>
            </dl>
            <div class="px-5 pb-5 flex flex-wrap gap-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                    <button type="submit" name="toggle_client" class="text-sm bg-transparent border-0 cursor-pointer p-0 <?= $client['status'] === 'active' ? 'text-red-400' : 'text-green-400' ?>"><?= $client['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                </form>
                <?php if (trim((string) $client['totp_secret']) !== ''): ?>
                    <form method="POST" onsubmit="return confirm('Reset Google Authenticator for this client?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                        <button type="submit" name="reset_authenticator" class="text-sm bg-transparent border-0 cursor-pointer p-0 text-slate-400">Reset authenticator</button>
                    </form>
                <?php endif; ?>
                <a class="text-brand-400 text-sm" href="sms-line.php?client=<?= (int) $client['id'] ?>">SMS history</a>
            </div>
        </section>
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Change email or mobile</h2></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-slate-500 text-xs">The client cannot change these. Both stay required.</p>
                <input name="contact_email" type="email" required maxlength="120" class="form-input" value="<?= e($client['email']) ?>">
                <input name="contact_phone" required inputmode="numeric" maxlength="16" class="form-input" value="<?= e($client['phone']) ?>">
                <button type="submit" name="set_contact" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save email and mobile</button>
            </form>
        </section>
    </div>

    <section class="dash-panel overflow-hidden">
        <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Services</h2></div>
        <?php if ($services): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-800 text-left">
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Service</th>
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Status</th>
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Renews</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-white"><?= e($service['service_name']) ?><?= trim((string) $service['detail_label']) !== '' ? ' · ' . e($service['detail_label']) : '' ?></td>
                                <td class="px-4 py-3 text-sm text-slate-300"><?= e(ucfirst((string) $service['status'])) ?></td>
                                <td class="px-4 py-3 text-sm text-slate-400"><?= trim((string) $service['next_renewal']) !== '' ? e($service['next_renewal']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="p-5 text-slate-500 text-sm">No services on this account yet.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
