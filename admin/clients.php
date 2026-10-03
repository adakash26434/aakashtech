<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$find = admin_find_text(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');
$findBack = $find === '' ? 'clients.php' : 'clients.php?q=' . rawurlencode($find);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_client'])) {
    verify_csrf();
    $id = (int) ($_POST['client_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE client_users SET status = CASE WHEN status = 'active' THEN 'suspended' ELSE 'active' END WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: ' . $findBack);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_client'])) {
    verify_csrf();
    $created = billing_admin_create_client(
        $conn,
        isset($_POST['new_name']) ? $_POST['new_name'] : '',
        isset($_POST['new_email']) ? $_POST['new_email'] : '',
        isset($_POST['new_phone']) ? $_POST['new_phone'] : '',
        isset($_POST['new_company']) ? $_POST['new_company'] : '',
        isset($_POST['new_password']) ? $_POST['new_password'] : ''
    );
    if (!empty($created['ok'])) {
        flash('client_notice', 'Account created. The client signs in with that email and password, then sets up Google Authenticator.');
    } else {
        flash('client_error', $created['error']);
    }
    header('Location: ' . $findBack);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    verify_csrf();
    $added = billing_admin_add_service(
        $conn,
        isset($_POST['service_client']) ? (int) $_POST['service_client'] : 0,
        isset($_POST['service_plan']) ? $_POST['service_plan'] : '',
        isset($_POST['service_quantity']) ? (int) $_POST['service_quantity'] : 0,
        isset($_POST['service_detail']) ? $_POST['service_detail'] : ''
    );
    if ($added === '') {
        flash('client_notice', 'Service added. The wallet was not charged. SMS or voice credits, if this plan includes them, are on the account now.');
    } else {
        flash('client_error', $added);
    }
    header('Location: ' . $findBack);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_contact'])) {
    verify_csrf();
    $contactError = billing_admin_set_contact(
        $conn,
        isset($_POST['contact_client']) ? (int) $_POST['contact_client'] : 0,
        isset($_POST['contact_email']) ? $_POST['contact_email'] : '',
        isset($_POST['contact_phone']) ? $_POST['contact_phone'] : ''
    );
    if ($contactError === '') {
        flash('client_notice', 'Email and mobile saved. The client cannot change them. A notice was sent to the address on the account.');
    } else {
        flash('client_error', $contactError);
    }
    header('Location: ' . $findBack);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_authenticator'])) {
    verify_csrf();
    $id = (int) (isset($_POST['client_id']) ? $_POST['client_id'] : 0);
    if ($id > 0) {
        totp_clear($conn, 'client', $id);
        flash('client_notice', 'Authenticator reset. That client sets it up again on the next sign-in.');
    }
    header('Location: ' . $findBack);
    exit;
}

$clientNotice = flash('client_notice');
$clientError = flash('client_error');
$servicePlans = array();
$planResult = $conn->query('SELECT code, name, needs_detail FROM service_plans WHERE is_active = 1 ORDER BY sort_order, id');
if ($planResult) {
    while ($planRow = $planResult->fetch_assoc()) {
        $servicePlans[] = $planRow;
    }
}

$clientRows = array();
if ($find === '') {
    $clientResult = $conn->query('SELECT * FROM client_users ORDER BY created_at DESC LIMIT 200');
    if ($clientResult) {
        while ($clientRow = $clientResult->fetch_assoc()) {
            $clientRows[] = $clientRow;
        }
    }
} else {
    $like = '%' . $find . '%';
    $clientStmt = $conn->prepare('SELECT * FROM client_users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ? ORDER BY created_at DESC LIMIT 50');
    $clientStmt->bind_param('ssss', $like, $like, $like, $like);
    $clientStmt->execute();
    $clientRows = db_fetch_all($clientStmt);
    $clientStmt->close();
}
$clientIds = array();
foreach ($clientRows as $clientRow) {
    $clientIds[] = (int) $clientRow['id'];
}
$clientFigures = sms_admin_client_figures($conn, $clientIds);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Clients</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Latest 200 accounts.' : 'Matches for “' . e($find) . '”.' ?> Create an account here when the person did not register on the website. SMS use and message history stay on <a class="text-brand-400" href="sms-line.php">SMS line</a>.</p>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Name, email, phone, or company">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>

<?php if ($clientNotice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($clientNotice) ?></div>
<?php endif; ?>
<?php if ($clientError !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($clientError) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Create an account</h3></div>
        <form method="POST" class="p-5 space-y-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
            <p class="text-slate-500 text-xs">Use this when you register the client yourself. They sign in, then set up Google Authenticator on the first visit.</p>
            <input name="new_name" required maxlength="80" class="form-input" placeholder="Name">
            <input name="new_email" type="email" required maxlength="254" class="form-input" placeholder="Email">
            <input name="new_phone" required inputmode="numeric" class="form-input" placeholder="10-digit mobile">
            <input name="new_company" maxlength="120" class="form-input" placeholder="Company, optional">
            <input name="new_password" type="text" required minlength="8" class="form-input" placeholder="Password, min 8 characters" autocomplete="off">
            <button type="submit" name="create_client" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Create account</button>
        </form>
    </div>
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add a paid service</h3></div>
        <form method="POST" class="p-5 space-y-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
            <p class="text-slate-500 text-xs">The wallet is not charged. Yearly or monthly services still renew from the wallet later. For SMS or voice, enter how many credits to add.</p>
            <select name="service_client" required class="form-input">
                <option value="">Client</option>
                <?php foreach ($clientRows as $choice): ?>
                    <option value="<?= (int) $choice['id'] ?>"><?= e($choice['name']) ?> · <?= e($choice['email']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="service_plan" required class="form-input">
                <option value="">Service</option>
                <?php foreach ($servicePlans as $servicePlan): ?>
                    <option value="<?= e($servicePlan['code']) ?>"><?= e($servicePlan['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="service_quantity" type="number" min="0" max="500000" class="form-input" placeholder="SMS or voice quantity, if that plan needs it">
            <input name="service_detail" maxlength="180" class="form-input" placeholder="Domain or note, optional">
            <button type="submit" name="add_service" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add service</button>
        </form>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Change email or mobile</h3></div>
    <form method="POST" class="p-5 space-y-3 max-w-xl">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
        <p class="text-slate-500 text-xs">The client cannot change the sign-in email or the mobile number. Use this only after they ask. Both stay required. The old email is told when the sign-in address changes, and any unused password-reset link is cancelled.</p>
        <select name="contact_client" required class="form-input">
            <option value="">Client</option>
            <?php foreach ($clientRows as $choice): ?>
                <option value="<?= (int) $choice['id'] ?>"><?= e($choice['name']) ?> · <?= e($choice['email']) ?> · <?= e($choice['phone']) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="contact_email" type="email" required maxlength="120" class="form-input" placeholder="Email">
        <input name="contact_phone" required inputmode="numeric" maxlength="16" class="form-input" placeholder="10-digit mobile">
        <button type="submit" name="set_contact" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save email and mobile</button>
    </form>
</div>

<div class="dash-panel overflow-hidden">
    <?php if ($clientRows): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden md:table-cell">Company</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Phone</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Joined</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">SMS used / left</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($clientRows as $cl): ?>
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
                                <?php $figure = isset($clientFigures[(int) $cl['id']]) ? $clientFigures[(int) $cl['id']] : array('used' => 0, 'left' => 0); ?>
                                <span class="text-white text-sm"><?= number_format((int) $figure['used']) ?> / <?= number_format((int) $figure['left']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="client_id" value="<?= (int) $cl['id'] ?>">
                                    <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
                                    <button type="submit" name="toggle_client" class="text-sm bg-transparent border-0 cursor-pointer p-0 <?= $cl['status'] === 'active' ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300' ?>"><?= $cl['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                                </form>
                                <?php if (isset($cl['totp_secret']) && $cl['totp_secret'] !== ''): ?>
                                    <form method="POST" class="mt-2" onsubmit="return confirm('Reset Google Authenticator for this client? They set it up again at the next sign-in.');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="client_id" value="<?= (int) $cl['id'] ?>">
                                        <?php if ($find !== ''): ?><input type="hidden" name="q" value="<?= e($find) ?>"><?php endif; ?>
                                        <button type="submit" name="reset_authenticator" class="text-xs bg-transparent border-0 cursor-pointer p-0 text-slate-400 hover:text-white">Reset authenticator</button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-slate-500 text-xs mt-2">Authenticator not set up</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm"><?= $find === '' ? 'No clients registered yet.' : 'No account matches that search.' ?></p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
