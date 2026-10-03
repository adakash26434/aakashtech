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
        'whatsapp_number' => 40,
        'viber_number' => 40,
        'messenger_url' => 200,
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
    $linkKeys = array('notice_link', 'messenger_url', 'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url', 'linkedin_url');
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
    } elseif ($values['whatsapp_number'] !== '' && site_chat_digits($values['whatsapp_number']) === '') {
        $err = 'WhatsApp needs a real mobile number. The number is used for the link and is not printed on the site.';
    } elseif ($values['viber_number'] !== '' && site_chat_digits($values['viber_number']) === '') {
        $err = 'Viber needs a real mobile number. The number is used for the link and is not printed on the site.';
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
                $values['site_phone'] = '';
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

    $stmt = $conn->prepare('SELECT password FROM admin_users WHERE id = ?');
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!auth_password_matches($row ? (string) $row['password'] : '', $current)) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $err = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $err = 'Passwords do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $admin_id);
        $stmt->execute();
        $stmt->close();
        auth_remember_password('admin', $hash);
        $msg = 'Password changed successfully.';
    }
}

$totpNote = array('msg' => '', 'err' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp_action'])) {
    $totpNote = totp_manage_post($conn, 'admin', (int) $_SESSION['admin_id']);
}
$totpView = totp_manage_view('admin', (int) $_SESSION['admin_id'], isset($_SESSION['admin_email']) ? (string) $_SESSION['admin_email'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_notify']) || isset($_POST['send_notify_test']))) {
    verify_csrf();
    $notifyEmail = strtolower(trim((string) (isset($_POST['notify_email']) ? $_POST['notify_email'] : '')));
    $mailFrom = strtolower(trim((string) (isset($_POST['mail_from']) ? $_POST['mail_from'] : '')));
    if ($mailFrom === '') {
        $mailFrom = site_sender_email();
    }
    if ($notifyEmail !== '' && !billing_mail_ok($notifyEmail)) {
        $err = 'Enter a valid email for request notifications.';
    } elseif (!billing_mail_ok($mailFrom)) {
        $err = 'Enter a valid sending address.';
    } else {
        billing_set_setting($conn, 'mail_from', $mailFrom);
        billing_set_setting($conn, 'notify_email', $notifyEmail);
        if (isset($_POST['send_notify_test'])) {
            $test = billing_notify_test($conn);
            if (!empty($test['ok'])) {
                $msg = 'The server accepted the test. Open ' . $notifyEmail . ', including the spam folder, and confirm the message is there.';
            } else {
                $err = $test['error'];
            }
        } else {
            $msg = $notifyEmail === '' ? 'Request emails are off until an address is saved.' : 'Request emails will go to ' . $notifyEmail . '.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai'])) {
    verify_csrf();
    $provider = isset($_POST['ai_provider']) ? (string) $_POST['ai_provider'] : '';
    if ($provider !== 'gemini' && $provider !== 'deepseek') {
        $provider = '';
    }
    $geminiInput = trim((string) (isset($_POST['ai_gemini_key']) ? $_POST['ai_gemini_key'] : ''));
    $deepseekInput = trim((string) (isset($_POST['ai_deepseek_key']) ? $_POST['ai_deepseek_key'] : ''));
    $geminiKey = billing_setting($conn, 'ai_gemini_key');
    $deepseekKey = billing_setting($conn, 'ai_deepseek_key');
    if (!empty($_POST['clear_gemini'])) {
        $geminiKey = '';
    } elseif ($geminiInput !== '') {
        $geminiKey = site_ai_key_ok($geminiInput) ? $geminiInput : null;
    }
    if (!empty($_POST['clear_deepseek'])) {
        $deepseekKey = '';
    } elseif ($deepseekInput !== '') {
        $deepseekKey = site_ai_key_ok($deepseekInput) ? $deepseekInput : null;
    }
    if ($geminiKey === null || $deepseekKey === null) {
        $err = 'A key should be the long value from Gemini or DeepSeek, with no spaces.';
    } elseif ($provider === 'gemini' && $geminiKey === '') {
        $err = 'Paste a Gemini key, or choose DeepSeek.';
    } elseif ($provider === 'deepseek' && $deepseekKey === '') {
        $err = 'Paste a DeepSeek key, or choose Gemini.';
    } else {
        billing_set_setting($conn, 'ai_provider', $provider);
        billing_set_setting($conn, 'ai_gemini_key', $geminiKey);
        billing_set_setting($conn, 'ai_deepseek_key', $deepseekKey);
        $msg = $provider === '' ? 'The public assistant is off.' : 'The public assistant will answer from the service pages.';
    }
}

$settings = site_public_settings($conn);
$notifyEmail = billing_notify_address($conn);
$mailFrom = billing_mail_from_address($conn);
$notifyTestAt = billing_setting($conn, 'notify_test_at');
$notifyTestResult = billing_setting($conn, 'notify_test_result');
$notifyTestError = billing_setting($conn, 'notify_test_error');
$notifyLastAt = billing_setting($conn, 'notify_last_at');
$notifyLastResult = billing_setting($conn, 'notify_last_result');
$aiProvider = billing_setting($conn, 'ai_provider');
$geminiSaved = billing_setting($conn, 'ai_gemini_key') !== '';
$deepseekSaved = billing_setting($conn, 'ai_deepseek_key') !== '';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Settings</h1>
    <p class="text-slate-500 text-sm">Logo, public contact details, and the inbox that receives new requests. <a class="text-brand-400" href="manual.php#settings">नेपाली चरण</a></p>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Request emails</h3></div>
    <form method="POST" action="" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">When a contact message, service order, domain request, paid domain, wallet top-up, identity check, or support ticket arrives, this address gets an email. You do not have to stay signed in to notice it.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="notify_email">Notification email</label>
            <input id="notify_email" type="email" name="notify_email" maxlength="120" class="form-input" value="<?= e($notifyEmail) ?>" placeholder="info@aakashtechnologies.com.np">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="mail_from">Sending address</label>
            <input id="mail_from" type="email" name="mail_from" maxlength="120" class="form-input" value="<?= e($mailFrom) ?>" placeholder="noreply@aakashtechnologies.com.np">
        </div>
        <p class="text-slate-500 text-xs">Queries and notices arrive at the notification email. Client mail is sent from the sending address. The default is noreply@aakashtechnologies.com.np. A reply goes to the public email below. On cPanel, that sending address should be a mailbox on this domain. Leave the notification email blank to stop admin notices. Client mail still goes out, and the requests still appear in the admin panel.</p>
        <?php if ($notifyTestAt !== ''): ?>
            <p class="text-slate-400 text-sm">Last test <?= e($notifyTestAt) ?>: <?= $notifyTestResult === 'accepted' ? 'the server accepted it. Confirm it is in the inbox.' : 'the server refused it.' ?><?= $notifyTestError !== '' ? ' ' . e($notifyTestError) : '' ?></p>
        <?php endif; ?>
        <?php if ($notifyLastAt !== ''): ?>
            <p class="text-slate-400 text-sm">Last request email <?= e($notifyLastAt) ?>: <?= $notifyLastResult === 'accepted' ? 'the server accepted it.' : 'the server refused it.' ?></p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-3">
            <button type="submit" name="save_notify" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save notification email</button>
            <button type="submit" name="send_notify_test" value="1" class="px-6 py-2.5 border border-slate-600 text-white text-sm font-medium rounded-xl transition">Send a test email</button>
        </div>
    </form>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Mail the client receives</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">These go out on their own as <?= e(site_sender_name()) ?> &lt;<?= e($mailFrom) ?>&gt;. The password is never written in the email.</p>
        <?php foreach (billing_mail_catalog() as $draft): ?>
            <div class="rounded-xl border border-slate-800 p-4">
                <p class="text-white text-sm font-medium"><?= e($draft['when']) ?></p>
                <p class="text-brand-300 text-xs mt-2">Subject: <?= e($draft['subject']) ?></p>
                <p class="text-slate-400 text-xs mt-2 whitespace-pre-wrap"><?= e(implode("\n", $draft['lines'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Public assistant</h3></div>
    <form method="POST" action="" class="p-5 space-y-4" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">Visitors can ask about the services from the public pages. The assistant reads those pages only. It is not given passwords, portal logins, wallet records, identity files, or payment account numbers.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_provider">Which key to use</label>
            <select id="ai_provider" name="ai_provider" class="form-input">
                <option value="" <?= $aiProvider === '' ? 'selected' : '' ?>>Off</option>
                <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>>Gemini</option>
                <option value="deepseek" <?= $aiProvider === 'deepseek' ? 'selected' : '' ?>>DeepSeek</option>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_gemini_key">Gemini key</label>
            <input id="ai_gemini_key" type="password" name="ai_gemini_key" maxlength="200" class="form-input" value="" autocomplete="new-password" placeholder="<?= $geminiSaved ? 'A key is saved. Paste a new one to replace it.' : 'Paste the Gemini key' ?>">
            <?php if ($geminiSaved): ?>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="clear_gemini" value="1"> Remove the saved Gemini key
                </label>
            <?php endif; ?>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_deepseek_key">DeepSeek key</label>
            <input id="ai_deepseek_key" type="password" name="ai_deepseek_key" maxlength="200" class="form-input" value="" autocomplete="new-password" placeholder="<?= $deepseekSaved ? 'A key is saved. Paste a new one to replace it.' : 'Paste the DeepSeek key' ?>">
            <?php if ($deepseekSaved): ?>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="clear_deepseek" value="1"> Remove the saved DeepSeek key
                </label>
            <?php endif; ?>
        </div>
        <p class="text-slate-500 text-xs">The key stays on the server. It is not shown again and it is not printed on the website. Gemini uses gemini-2.5-flash. DeepSeek uses deepseek-chat. Choose Off to hide the assistant.</p>
        <button type="submit" name="save_ai" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save assistant</button>
    </form>
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
                <label class="block text-slate-400 text-xs font-medium mb-1.5">WhatsApp number</label>
                <input type="text" name="whatsapp_number" maxlength="40" class="form-input" value="<?= e($settings['whatsapp_number'] ?? '') ?>" placeholder="10-digit mobile for the chat link">
                <p class="text-slate-500 text-xs mt-1.5">Used only to open WhatsApp. The number is not printed on the site. A 10-digit Nepal mobile is sent as 977…</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Viber number</label>
                <input type="text" name="viber_number" maxlength="40" class="form-input" value="<?= e($settings['viber_number'] ?? '') ?>" placeholder="10-digit mobile for the chat link">
                <p class="text-slate-500 text-xs mt-1.5">Used only to open Viber. The number is not printed on the site.</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Messenger link</label>
                <input type="url" name="messenger_url" maxlength="200" class="form-input" value="<?= e($settings['messenger_url'] ?? '') ?>" placeholder="https://m.me/your-page">
                <p class="text-slate-500 text-xs mt-1.5">Paste the page’s https://m.me/ link. It is not invented here.</p>
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
                <p class="text-slate-500 text-xs mt-2">Only filled links appear as icons in the footer. WhatsApp, Viber, and Messenger use the fields above. A personal mobile is not shown as a call number.</p>
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Online payment</h4>
                <div class="space-y-3">
                    <input type="text" name="esewa_id" maxlength="40" class="form-input" placeholder="eSewa ID" value="<?= e($settings['esewa_id'] ?? '') ?>" aria-label="eSewa ID">
                    <input type="text" name="khalti_id" maxlength="40" class="form-input" placeholder="Khalti ID" value="<?= e($settings['khalti_id'] ?? '') ?>" aria-label="Khalti ID">
                </div>
                <p class="text-slate-500 text-xs mt-2">eSewa and Khalti appear for the client only after an ID is saved here. A blank field keeps the value from cpanel-config.php.</p>
                <h4 class="font-heading font-semibold text-white text-sm mt-5 mb-3">Manual payment</h4>
                <textarea name="bank_details" maxlength="400" rows="3" class="form-input" placeholder="Bank name, account name, and account number" aria-label="Bank details"><?= e($settings['bank_details'] ?? '') ?></textarea>
                <p class="text-slate-500 text-xs mt-2">Bank transfer appears only after these details are saved. Leave it blank to hide manual payment.</p>
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
    <?php require __DIR__ . '/../includes/totp-manage-card.php'; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
