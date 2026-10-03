<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$cid = get_client_id();
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
    $client = array('name' => get_client_name(), 'email' => '', 'phone' => '', 'company' => '', 'address' => '', 'status' => 'active', 'avatar_color' => '#0b8b7a', 'password' => '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf();
    $name = substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    $phoneInput = trim((string) ($_POST['phone'] ?? ''));
    $phone = $phoneInput === '' ? '' : auth_mobile_number($phoneInput);
    $company = substr(trim((string) ($_POST['company'] ?? '')), 0, 120);
    $address = substr(trim((string) ($_POST['address'] ?? '')), 0, 300);
    if ($name === '') {
        $err = 'Name is required.';
    } elseif ($phoneInput !== '' && $phone === '') {
        $err = 'Enter a 10-digit mobile number.';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE client_users SET name = ?, phone = ?, company = ?, address = ? WHERE id = ?");
            if (!$stmt) {
                throw new RuntimeException('Profile update failed.');
            }
            $stmt->bind_param("ssssi", $name, $phone, $company, $address, $cid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['client_name'] = $name;
            $msg = 'Profile updated successfully!';
            $client['name'] = $name;
            $client['phone'] = $phone;
            $client['company'] = $company;
            $client['address'] = $address;
        } catch (Throwable $exception) {
            error_log('Client profile could not be saved.');
            $err = 'Failed to update profile.';
        }
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
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">My Profile</h1>
    <p class="text-slate-500 text-sm">Name, mobile, password, and the authenticator code for sign-in.</p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Profile Info -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Account Information</h3></div>
        <form method="POST" action="" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php $avatar = (isset($client['avatar_color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', $client['avatar_color'])) ? $client['avatar_color'] : '#0b8b7a'; ?>
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
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Email (cannot change)</label>
                <input type="email" class="form-input opacity-50 cursor-not-allowed" value="<?= e($client['email']) ?>" disabled>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Mobile (10 digits)</label>
                <input type="tel" name="phone" inputmode="tel" maxlength="16" autocomplete="tel" class="form-input" placeholder="10-digit mobile" value="<?= e($client['phone'] ?? '') ?>">
            </div>
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
        <form method="POST" action="" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Current Password</label>
                <input type="password" name="current_pass" required class="form-input" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">New Password</label>
                <input type="password" name="new_pass" required class="form-input" placeholder="Min 8 characters">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Confirm New Password</label>
                <input type="password" name="confirm_pass" required class="form-input" placeholder="Repeat new password">
            </div>
            <button type="submit" name="change_password" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Change Password</button>
        </form>
    </div>
    <?php require __DIR__ . '/../includes/totp-manage-card.php'; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
