<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = get_client_id();
require_once __DIR__ . '/../includes/profile-view.php';
$msg = '';
$err = '';

$client = null;
try {
    $clientStmt = $conn->prepare('SELECT * FROM client_users WHERE id = ?');
    $clientStmt->bind_param('i', $cid);
    $clientStmt->execute();
    $client = db_fetch_assoc($clientStmt);
    $clientStmt->close();
} catch (Throwable $exception) {
    error_log('Client profile could not be read.');
}
if (!$client) {
    $client = array('name' => get_client_name(), 'email' => '', 'phone' => '', 'company' => '', 'address' => '', 'status' => 'active', 'avatar_color' => '#097a6d', 'password' => '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf();
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $company = isset($_POST['company']) ? $_POST['company'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $saved = billing_client_save_profile($conn, $cid, $name, $company, $address);
    if ($saved !== '') {
        $err = $saved;
        $client['name'] = billing_plain_line($name, 80);
        $client['company'] = billing_plain_line($company, 120);
        $client['address'] = billing_plain_block($address, 300);
    } else {
        $client['name'] = billing_plain_line($name, 80);
        $client['company'] = billing_plain_line($company, 120);
        $client['address'] = billing_plain_block($address, 300);
        $_SESSION['client_name'] = $client['name'];
        $msg = 'Profile updated successfully!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verify_csrf();
    $current = $_POST['current_pass'] ?? '';
    $new = $_POST['new_pass'] ?? '';
    $confirm = $_POST['confirm_pass'] ?? '';
    if (!auth_password_matches((string) $client['password'], $current)) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } elseif (strlen($new) > 72) {
        $err = 'New password must be 72 characters or fewer.';
    } elseif ($new !== $confirm) {
        $err = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE client_users SET password = ? WHERE id = ?");
            if (!$stmt) {
                throw new RuntimeException('Password update failed.');
            }
            $stmt->bind_param("si", $hash, $cid);
            $stmt->execute();
            $stmt->close();
            $client['password'] = $hash;
            auth_remember_password('client', $hash);
            password_reset_clear($conn, $cid);
            $msg = 'Password changed successfully!';
        } catch (Throwable $exception) {
            error_log('Client password could not be saved.');
            $err = 'Failed to update password.';
        }
    }
}

$totpNote = array('msg' => '', 'err' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp_action'])) {
    $totpNote = totp_manage_post($conn, 'client', $cid);
}
$totpView = totp_manage_view('client', $cid, isset($client['email']) ? (string) $client['email'] : '');
$kycNow = billing_kyc_load($conn, (int) $cid);
$checklist = profile_checklist($client, (string) $kycNow['status']);
$memberSince = !empty($client['created_at']) ? date('M Y', strtotime($client['created_at'])) : '';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">My Profile</h1>
    <p class="text-slate-500 text-sm">Name, company, and address can be updated here. The sign-in email and mobile stay as they were registered.</p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<section class="pro-card" aria-label="Account at a glance">
    <div class="pro-who">
        <?php $avatarTop = (isset($client['avatar_color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', $client['avatar_color'])) ? $client['avatar_color'] : '#097a6d'; ?>
        <span class="pro-avatar" style="background: <?= e($avatarTop) ?>"><?= e(strtoupper(substr((string) $client['name'], 0, 1))) ?></span>
        <div><strong><?= e($client['name']) ?></strong><span><?= e($client['email']) ?><?= !empty($client['phone']) ? ' · ' . e($client['phone']) : '' ?></span><small><?= $memberSince !== '' ? 'Member since ' . e($memberSince) : '' ?></small></div>
    </div>
    <div class="pro-check">
        <div class="pro-bar" role="img" aria-label="<?= (int) $checklist['percent'] ?> percent of your account is set up"><span style="width:<?= (int) $checklist['percent'] ?>%"></span></div>
        <p><?= (int) $checklist['done'] ?> of <?= (int) $checklist['total'] ?> set up</p>
        <ul>
            <?php foreach ($checklist['items'] as $item): ?>
                <li class="<?= $item['done'] ? 'is-done' : '' ?>"><a href="<?= e($item['href']) ?>"><i aria-hidden="true"><?= $item['done'] ? '✓' : '○' ?></i><span><b><?= e($item['title']) ?></b><small><?= e($item['help']) ?></small></span></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Profile Info -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Account Information</h3></div>
        <form method="POST" action="" class="p-5 space-y-4" id="profile-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php $avatar = (isset($client['avatar_color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', $client['avatar_color'])) ? $client['avatar_color'] : '#097a6d'; ?>
            <div class="flex items-center gap-4 mb-2">
                <div class="portal-avatar-letter w-16 h-16 rounded-2xl flex items-center justify-center font-heading font-bold text-white text-2xl" style="background: <?= e($avatar) ?>"><?= strtoupper(substr($client['name'], 0, 1)) ?></div>
                <div>
                    <p class="text-white font-medium"><?= e($client['name']) ?></p>
                    <p class="text-slate-500 text-xs"><?= e($client['email']) ?></p>
                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-medium rounded-full <?= $client['status'] === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>"><?= ucfirst($client['status']) ?></span>
                </div>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Full Name *</label>
                <input type="text" name="name" required class="form-input" value="<?= e($client['name']) ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Email</label>
                <input type="email" class="form-input opacity-50 cursor-not-allowed" value="<?= e($client['email']) ?>" disabled>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Mobile</label>
                <input type="tel" class="form-input opacity-50 cursor-not-allowed" value="<?= e($client['phone'] ?? '') ?>" disabled>
            </div>
            <p class="text-slate-500 text-xs">The sign-in email and mobile are required and stay fixed. Ask the team through Support if either one must change.</p>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Company</label>
                <input type="text" name="company" class="form-input" value="<?= e($client['company'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Address</label>
                <textarea name="address" rows="2" class="form-input resize-none" placeholder="Your address"><?= e($client['address'] ?? '') ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Change Password</h3></div>
        <form method="POST" action="" class="p-5 space-y-4 pro-pass" id="password-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block" for="current_pass">Current password</label>
                <input id="current_pass" type="password" name="current_pass" required autocomplete="current-password" class="form-input"></div>
            <div><label class="block" for="new_pass">New password</label>
                <input id="new_pass" type="password" name="new_pass" required minlength="8" autocomplete="new-password" class="form-input" aria-describedby="pass-help" data-meter="pass-meter">
                <div class="pro-meter" id="pass-meter" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                <small class="field-hint" id="pass-help">At least 8 characters. A few words together, with a number, is strong and easy to remember.</small></div>
            <div><label class="block" for="confirm_pass">Type the new password again</label>
                <input id="confirm_pass" type="password" name="confirm_pass" required autocomplete="new-password" class="form-input" data-match="new_pass">
                <small class="field-hint" id="match-note" aria-live="polite"></small></div>
            <label class="pro-show"><input type="checkbox" id="show-pass"> Show the passwords</label>
            <button type="submit" name="change_password" class="co-pay">Change password</button>
        </form>
    </div>
    <?php require __DIR__ . '/../includes/totp-manage-card.php'; ?>
</div>
<script defer src="../assets/js/password.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
