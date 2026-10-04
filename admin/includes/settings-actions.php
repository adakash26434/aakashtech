<?php
/**
 * Admin Settings: handles the form posts and prepares the values the page shows.
 * Included by admin/settings.php, so it shares that page's variables ($conn, $msg, $err ...).
 */

$msg = '';
$err = '';
$homeDraft = array();

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
                $dir = dirname(__DIR__, 2) . '/uploads';
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
                    $dir = dirname(__DIR__, 2) . '/uploads';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_homepage'])) {
    verify_csrf();
    $homeFields = array(
        'home_eyebrow' => 180,
        'home_title' => 240,
        'home_lede' => 600,
        'home_ribbon' => 180,
        'home_services_kicker' => 80,
        'home_services_heading' => 160,
        'home_services_text' => 800,
        'home_about_heading' => 180,
        'home_about_text' => 800,
        'home_process_heading' => 180,
        'home_process_text' => 800,
        'home_contact_heading' => 180
    );
    $homeValues = array();
    $homeMissing = false;
    foreach ($homeFields as $key => $limit) {
        $value = substr(trim(isset($_POST[$key]) ? (string) $_POST[$key] : ''), 0, $limit);
        if ($value === '') {
            $homeMissing = true;
        }
        $homeValues[$key] = $value;
    }
    if ($homeMissing) {
        $err = 'Fill every homepage line. Nothing was saved.';
        $homeDraft = $homeValues;
    } else {
        foreach ($homeValues as $key => $value) {
            billing_set_setting($conn, $key, $value);
        }
        $msg = 'Homepage text saved. The public site uses it now.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_legal'])) {
    verify_csrf();
    $privacy = !empty($_POST['restore_privacy']) ? '' : site_legal_plain(isset($_POST['privacy_policy']) ? $_POST['privacy_policy'] : '', 12000);
    $cookies = !empty($_POST['restore_cookies']) ? '' : site_legal_plain(isset($_POST['cookie_policy']) ? $_POST['cookie_policy'] : '', 12000);
    billing_set_setting($conn, 'privacy_policy', $privacy);
    billing_set_setting($conn, 'cookie_policy', $cookies);
    $terms = !empty($_POST['restore_terms']) ? '' : site_legal_plain(isset($_POST['terms_of_service']) ? $_POST['terms_of_service'] : '', 12000);
    billing_set_setting($conn, 'terms_of_service', $terms);
    $msg = 'Privacy policy, cookie notice, and terms saved. They appear in the public footer.';
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
if (!empty($homeDraft) && is_array($homeDraft)) {
    foreach ($homeDraft as $key => $value) {
        $settings[$key] = $value;
    }
}
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
