<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    verify_csrf();
    $fields = array(
        'site_name' => 80,
        'site_email' => 120,
        'site_phone' => 40,
        'whatsapp_number' => 40,
        'site_location' => 120,
        'footer_tagline' => 180,
        'footer_text' => 180,
        'notice_title' => 80,
        'notice_body' => 500,
        'notice_link' => 200,
        'notice_link_label' => 40,
        'facebook_url' => 200,
        'instagram_url' => 200,
        'youtube_url' => 200,
        'tiktok_url' => 200,
        'linkedin_url' => 200,
        'esewa_id' => 40,
        'khalti_id' => 40,
        'bank_details' => 400
    );
    $values = array();
    foreach ($fields as $key => $limit) {
        $values[$key] = substr(trim($_POST[$key] ?? ''), 0, $limit);
    }
    $values['notice_enabled'] = !empty($_POST['notice_enabled']) ? '1' : '0';
    $linkKeys = array('notice_link', 'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url', 'linkedin_url');
    $badLink = false;
    foreach ($linkKeys as $linkKey) {
        if ($values[$linkKey] === '') {
            continue;
        }
        $safeLink = site_public_url($values[$linkKey]);
        if ($safeLink === '') {
            $badLink = true;
            break;
        }
        $values[$linkKey] = $safeLink;
    }
    if ($values['site_name'] === '') {
        $err = 'Site name is required.';
    } elseif ($values['site_email'] !== '' && !filter_var($values['site_email'], FILTER_VALIDATE_EMAIL)) {
        $err = 'Enter a valid public email address.';
    } elseif ($badLink) {
        $err = 'Social and notice links must start with https://';
    } else {
        $logo = site_store_logo(isset($_FILES['logo']) ? $_FILES['logo'] : array());
        if (!$logo['ok']) {
            $err = $logo['error'];
        } else {
            if (!empty($_POST['remove_logo'])) {
                $dir = dirname(__DIR__) . '/uploads';
                foreach (glob($dir . '/site-logo.*') as $old) {
                    if (is_file($old)) {
                        unlink($old);
                    }
                }
                $values['logo_path'] = '';
            } elseif ($logo['path'] !== null) {
                $values['logo_path'] = $logo['path'];
            }
            $noticeImage = site_store_notice_image(isset($_FILES['notice_image']) ? $_FILES['notice_image'] : array());
            if (!$noticeImage['ok']) {
                $err = $noticeImage['error'];
            } else {
                if (!empty($_POST['remove_notice_image']) && $noticeImage['path'] === null) {
                    $dir = dirname(__DIR__) . '/uploads';
                    foreach (glob($dir . '/site-notice.*') as $old) {
                        if (is_file($old)) {
                            unlink($old);
                        }
                    }
                    $values['notice_image'] = '';
                } elseif ($noticeImage['path'] !== null) {
                    $values['notice_image'] = $noticeImage['path'];
                }
                foreach ($values as $key => $value) {
                    billing_set_setting($conn, $key, $value);
                }
                billing_set_setting($conn, 'public_details_managed', '1');
                $msg = 'Public site details saved.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verify_csrf();
    $current = $_POST['current_pass'] ?? '';
    $new = $_POST['new_pass'] ?? '';
    $confirm = $_POST['confirm_pass'] ?? '';
    $admin_id = (int)$_SESSION['admin_id'];

    $row = $conn->query("SELECT password FROM admin_users WHERE id=$admin_id")->fetch_assoc();
    if (!password_verify($current, $row['password'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $err = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $err = 'Passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $admin_id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Password changed successfully.';
    }
}

$settings = site_public_settings($conn);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Settings</h1>
    <p class="text-slate-500 text-sm">Logo, name, and the contact details shown on the public site</p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Site Settings -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Site Information</h3></div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php $logoPreview = site_logo_web_path($settings['logo_path'] ?? ''); ?>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Logo</label>
                <?php if ($logoPreview !== ''): ?>
                    <img src="<?= e($logoPreview) ?>" alt="Current logo" class="mb-3 h-14 w-14 rounded-xl object-contain bg-white p-1">
                <?php endif; ?>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                <p class="text-slate-500 text-xs mt-1.5">PNG, JPG, WEBP, or GIF. Up to 2 MB. Shown in the header, footer, and both portals.</p>
                <?php if ($logoPreview !== ''): ?>
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remove_logo" value="1"> Remove the current logo
                    </label>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Site name</label>
                <input type="text" name="site_name" maxlength="80" class="form-input" value="<?= e($settings['site_name'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Public email</label>
                <input type="email" name="site_email" maxlength="120" class="form-input" value="<?= e($settings['site_email'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Public phone</label>
                <input type="text" name="site_phone" maxlength="40" class="form-input" value="<?= e($settings['site_phone'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">WhatsApp number</label>
                <input type="text" name="whatsapp_number" maxlength="40" class="form-input" value="<?= e($settings['whatsapp_number'] ?? '') ?>" placeholder="Same as the phone if left blank">
                <p class="text-slate-500 text-xs mt-1.5">Used for the query link in the footer and on the contact section. A 10-digit Nepal mobile is sent as 977…</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Location</label>
                <input type="text" name="site_location" maxlength="120" class="form-input" value="<?= e($settings['site_location'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Line under the logo</label>
                <input type="text" name="footer_tagline" maxlength="180" class="form-input" value="<?= e($settings['footer_tagline'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Footer note</label>
                <input type="text" name="footer_text" maxlength="180" class="form-input" value="<?= e($settings['footer_text'] ?? '') ?>">
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Popup notice</h4>
                <label class="flex items-center gap-2 text-sm text-slate-300 mb-3">
                    <input type="checkbox" name="notice_enabled" value="1" <?= (($settings['notice_enabled'] ?? '') === '1') ? 'checked' : '' ?>>
                    Show this notice when someone opens the public site
                </label>
                <div class="space-y-3">
                    <input type="text" name="notice_title" maxlength="80" class="form-input" placeholder="Title" value="<?= e($settings['notice_title'] ?? '') ?>">
                    <textarea name="notice_body" maxlength="500" rows="3" class="form-input" placeholder="The notice visitors should read"><?= e($settings['notice_body'] ?? '') ?></textarea>
                    <input type="url" name="notice_link" maxlength="200" class="form-input" placeholder="Optional https:// link" value="<?= e($settings['notice_link'] ?? '') ?>">
                    <input type="text" name="notice_link_label" maxlength="40" class="form-input" placeholder="Link label, such as Read more" value="<?= e($settings['notice_link_label'] ?? '') ?>">
                    <?php $noticePreview = site_notice_file($settings['notice_image'] ?? ''); ?>
                    <?php if ($noticePreview !== ''): ?>
                        <img src="../<?= e($noticePreview) ?>" alt="Current notice image" class="w-full max-h-40 object-contain rounded-xl bg-white">
                    <?php endif; ?>
                    <input type="file" name="notice_image" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                    <?php if ($noticePreview !== ''): ?>
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="remove_notice_image" value="1"> Remove the notice image
                        </label>
                    <?php endif; ?>
                </div>
                <p class="text-slate-500 text-xs mt-2">The image is optional. PNG, JPG, WEBP, or GIF, up to 2 MB. Turn the notice off when it is no longer needed. Closing it hides that same notice until the browser is opened again, or until you change the text or image.</p>
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Social contact</h4>
                <div class="space-y-3">
                    <input type="url" name="facebook_url" maxlength="200" class="form-input" placeholder="Facebook https:// link" value="<?= e($settings['facebook_url'] ?? '') ?>">
                    <input type="url" name="instagram_url" maxlength="200" class="form-input" placeholder="Instagram https:// link" value="<?= e($settings['instagram_url'] ?? '') ?>">
                    <input type="url" name="youtube_url" maxlength="200" class="form-input" placeholder="YouTube https:// link" value="<?= e($settings['youtube_url'] ?? '') ?>">
                    <input type="url" name="tiktok_url" maxlength="200" class="form-input" placeholder="TikTok https:// link" value="<?= e($settings['tiktok_url'] ?? '') ?>">
                    <input type="url" name="linkedin_url" maxlength="200" class="form-input" placeholder="LinkedIn https:// link" value="<?= e($settings['linkedin_url'] ?? '') ?>">
                </div>
                <p class="text-slate-500 text-xs mt-2">Only filled links appear as icons in the footer. WhatsApp uses the number above.</p>
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Wallet payment details</h4>
                <div class="space-y-3">
                    <input type="text" name="esewa_id" maxlength="40" class="form-input" placeholder="eSewa ID" value="<?= e($settings['esewa_id'] ?? '') ?>">
                    <input type="text" name="khalti_id" maxlength="40" class="form-input" placeholder="Khalti ID" value="<?= e($settings['khalti_id'] ?? '') ?>">
                    <textarea name="bank_details" maxlength="400" rows="3" class="form-input" placeholder="Bank name, account name, and account number"><?= e($settings['bank_details'] ?? '') ?></textarea>
                </div>
                <p class="text-slate-500 text-xs mt-2">Shown when a client adds wallet funds. A blank field keeps the value from cpanel-config.php.</p>
            </div>
            <button type="submit" name="update_settings" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save public details</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Change Password</h3></div>
        <form method="POST" action="" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Current Password</label>
                <input type="password" name="current_pass" required autocomplete="current-password" class="form-input" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">New Password</label>
                <input type="password" name="new_pass" required autocomplete="new-password" class="form-input" placeholder="•••••••••">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Confirm New Password</label>
                <input type="password" name="confirm_pass" required autocomplete="new-password" class="form-input" placeholder="•••••••••">
            </div>
            <button type="submit" name="change_password" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Change Password</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
