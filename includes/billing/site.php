<?php
/**
 * Billing: Public site settings, contact links, logos and posters.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function site_hero_title_html($text)
{
    $lines = preg_split('/\r\n|\r|\n/', trim((string) $text));
    if (!is_array($lines)) {
        $lines = array();
    }
    $clean = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }
    if (!$clean) {
        return '';
    }
    $last = count($clean) - 1;
    $html = array();
    foreach ($clean as $index => $line) {
        $safe = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($index === $last && preg_match('/^(.*\s)(\S+)$/u', $line, $match)) {
            $safe = htmlspecialchars($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<span>' . htmlspecialchars($match[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }
        $html[] = $safe;
    }
    return implode('<br>', $html);
}

function site_public_defaults()
{
    return array(
        'site_name' => defined('SITE_NAME') ? SITE_NAME : 'Aakash Technologies',
        'site_email' => site_email_or_official(defined('SITE_EMAIL') ? SITE_EMAIL : ''),
        'site_phone' => '',
        'whatsapp_number' => '',
        'viber_number' => '',
        'messenger_url' => '',
        'site_location' => defined('SITE_LOCATION') ? SITE_LOCATION : 'Kathmandu, Nepal',
        'company_legal_name' => '',
        'company_registration' => '',
        'company_pan' => '',
        'office_hours' => '',
        'notice_enabled' => '0',
        'notice_title' => '',
        'notice_body' => '',
        'notice_link' => '',
        'notice_link_label' => '',
        'notice_image' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'tiktok_url' => '',
        'linkedin_url' => '',
        'footer_tagline' => 'Practical technology for businesses ready to grow.',
        'footer_text' => 'Designed and built in Nepal.',
        'home_eyebrow' => 'For cooperatives, companies, parties, and personal work',
        'home_title' => "Bulk SMS.\nVoice calls.\nHosting and servers.",
        'home_lede' => 'Send the AGM, election, school, or festival notice, then keep the domain, the mail, and the website with the same company. The rate is on the page. The bill adds 13% VAT before you pay.',
        'home_ribbon' => 'Buy what you need. Recurring plans renew themselves.',
        'home_services_kicker' => 'What we do',
        'home_services_heading' => 'Read the rate. Buy it, or book it.',
        'home_services_text' => 'Open a service and see the rate before you create an account. Check a domain name, or open WHOIS check up to see who holds it. SMS and voice let you type a quantity and see the bill with 13% VAT. The same account covers hosting, domain email, the website, and field training.',
        'home_about_heading' => 'The rate is on the page. The order is the brief.',
        'home_about_text' => 'A cooperative can send the notice, register the name, host the site, open domain email, and train the people from one account. Companies, parties, schools, and personal use follow the same path.',
        'home_process_heading' => 'Buy it, then let it renew.',
        'home_process_text' => 'Create a client account, add wallet funds, and choose a plan. The first top-up is confirmed once. After that, checkout and renewals use the wallet.',
        'home_contact_heading' => 'Rates and orders are already online.',
        'privacy_policy' => '',
        'cookie_policy' => '',
        'terms_of_service' => '',
        'logo_path' => '',
        'favicon_path' => '',
        'esewa_id' => defined('ESEWA_ID') ? ESEWA_ID : '',
        'khalti_id' => defined('KHALTI_ID') ? KHALTI_ID : '',
        'bank_details' => defined('BANK_DETAILS') ? BANK_DETAILS : ''
    );
}

function site_apply_mail_addresses($conn)
{
    $current = strtolower(trim(billing_setting($conn, 'site_email')));
    if ($current === '' || $current === 'info@aakashtechnologies.com') {
        billing_set_setting($conn, 'site_email', site_official_email());
    }
    $key = 'notify_email';
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    if ($stmt) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        if (!$row) {
            billing_set_setting($conn, 'notify_email', site_official_email());
        } else {
            $notify = strtolower(trim((string) $row['setting_value']));
            if ($notify === 'info@aakashtechnologies.com') {
                billing_set_setting($conn, 'notify_email', site_official_email());
            }
        }
    }
    $from = strtolower(trim(billing_setting($conn, 'mail_from')));
    if (!billing_mail_ok($from)) {
        billing_set_setting($conn, 'mail_from', site_sender_email());
    }
}

function site_ensure_public_settings($conn)
{
    site_apply_mail_addresses($conn);
    foreach (site_public_defaults() as $key => $value) {
        $stmt = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $exists = db_fetch_assoc($stmt);
        $stmt->close();
        if (!$exists) {
            billing_set_setting($conn, $key, $value);
        }
    }
}

function site_public_settings($conn)
{
    $settings = site_public_defaults();
    if (!$conn) {
        return $settings;
    }
    try {
        $stored = array();
        $result = $conn->query('SELECT setting_key, setting_value FROM site_settings');
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stored[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
        }
    } catch (Throwable $exception) {
        return $settings;
    }
    foreach ($settings as $key => $value) {
        if (strpos($key, 'home_') !== 0) {
            continue;
        }
        if (isset($stored[$key]) && trim($stored[$key]) !== '') {
            $settings[$key] = $stored[$key];
        }
    }
    $managed = isset($stored['public_details_managed']) && $stored['public_details_managed'] === '1';
    if (!$managed) {
        if (!empty($stored['logo_path'])) {
            $settings['logo_path'] = $stored['logo_path'];
        }
        if (!empty($stored['favicon_path'])) {
            $settings['favicon_path'] = $stored['favicon_path'];
        }
        foreach (array('privacy_policy', 'cookie_policy', 'terms_of_service') as $legalKey) {
            if (isset($stored[$legalKey]) && trim($stored[$legalKey]) !== '') {
                $settings[$legalKey] = $stored[$legalKey];
            }
        }
        return $settings;
    }
    foreach ($settings as $key => $value) {
        if (!array_key_exists($key, $stored)) {
            continue;
        }
        if (strpos($key, 'home_') === 0 && trim($stored[$key]) === '') {
            continue;
        }
        $settings[$key] = $stored[$key];
    }
    return $settings;
}

function site_mail_href($email, $siteName)
{
    $email = trim((string) $email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    $subject = 'Query for ' . trim((string) $siteName);
    return 'mailto:' . $email . '?subject=' . rawurlencode($subject);
}

function site_public_url($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return '';
    }
    return $value;
}

function site_social_links($settings)
{
    $icons = array(
        'facebook_url' => array('Facebook', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H8v3h3v7h3v-7h2.6l.4-3H14v-1c0-.6.4-1 1-1z"/></svg>'),
        'instagram_url' => array('Instagram', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 3h8a5 5 0 0 1 5 5v8a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5V8a5 5 0 0 1 5-5zm8 2H8a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3zm-4 3.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 2A1.8 1.8 0 1 0 13.8 12 1.8 1.8 0 0 0 12 10.2zM17.2 6.6a1 1 0 1 1-1 1 1 1 0 0 1 1-1z"/></svg>'),
        'youtube_url' => array('YouTube', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12.2s0-3.2-.4-4.6a3 3 0 0 0-2.1-2.1C18.9 5 12 5 12 5s-6.9 0-8.5.5a3 3 0 0 0-2.1 2.1C1 9 1 12.2 1 12.2s0 3.2.4 4.6a3 3 0 0 0 2.1 2.1C5.1 19.4 12 19.4 12 19.4s6.9 0 8.5-.5a3 3 0 0 0 2.1-2.1c.4-1.4.4-4.6.4-4.6zM9.8 15.5v-6.6l6.2 3.3z"/></svg>'),
        'tiktok_url' => array('TikTok', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 3h2.2a5.2 5.2 0 0 0 3.6 3.4v2.3a7.4 7.4 0 0 1-3.6-1v6.6a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.1v2.4a3.3 3.3 0 1 0 2.4 3.2V3z"/></svg>'),
        'linkedin_url' => array('LinkedIn', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 9H4V20h2.5zM5.2 4A1.6 1.6 0 1 0 5.2 7.2 1.6 1.6 0 0 0 5.2 4zM20 20h-2.5v-5.6c0-1.6-.6-2.6-2-2.6a2.2 2.2 0 0 0-2 1.5 2 2 0 0 0-.1.9V20H11V9h2.4v1.5a3.4 3.4 0 0 1 3-1.7c2.2 0 3.6 1.4 3.6 4.4z"/></svg>')
    );
    $links = array();
    foreach ($icons as $key => $meta) {
        $href = site_public_url(isset($settings[$key]) ? $settings[$key] : '');
        if ($href === '') {
            continue;
        }
        $links[] = array('label' => $meta[0], 'href' => $href, 'icon' => $meta[1]);
    }
    return $links;
}

function site_chat_digits($number)
{
    $raw = trim((string) $number);
    if ($raw === '' || stripos($raw, 'x') !== false) {
        return '';
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if (!is_string($digits) || strlen($digits) < 8 || strlen($digits) > 15) {
        return '';
    }
    if (strlen($digits) === 10) {
        $digits = '977' . $digits;
    }
    return $digits;
}

function site_whatsapp_href($number)
{
    $digits = site_chat_digits($number);
    if ($digits === '') {
        return '';
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode('Hello, I have a query.');
}

function site_viber_href($number)
{
    $digits = site_chat_digits($number);
    if ($digits === '') {
        return '';
    }
    return 'viber://chat?number=%2B' . $digits;
}

function site_chat_channels($settings)
{
    $settings = is_array($settings) ? $settings : array();
    $icons = array(
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l5.9-1.1A11 11 0 0 0 20.5 3.5zM12 20.3a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3.5.7.7-3.4-.2-.3A8.3 8.3 0 1 1 12 20.3zm4.6-6.2c-.3-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.6.1a6.8 6.8 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.2-.3a.5.5 0 0 0 0-.5c-.1-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4 4.2 4.2 0 0 0 3 .4 2.5 2.5 0 0 0 1.6-1.2 2 2 0 0 0 .1-1.2c-.1-.1-.3-.2-.6-.3z"/></svg>',
        'viber' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.2 3C7.6 3 4.4 5.2 4 9.4c-.2 2.2.4 4 1.5 5.4L4.6 19l4.1-1.1c1 .5 2.1.8 3.3.8 4.7 0 8-2.4 8.2-6.7.2-4.2-3.2-9-8-9zm3.8 10.2c-.2.5-1 .9-1.6 1-.4.1-.9.2-2.6-.6-2.2-1-3.6-3.3-3.7-3.5-.1-.2-.9-1.2-.9-2.3s.6-1.6.8-1.8.4-.3.6-.3h.4c.1 0 .3 0 .5.4.2.5.6 1.6.7 1.7.1.1.1.3 0 .5-.1.2-.2.3-.3.5l-.2.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.6.9.8 1.6 1 1.8 1.1.2.1.4.1.5-.1.1-.2.6-.7.8-.9.2-.2.3-.2.5-.1.2.1 1.4.7 1.6.8.2.1.4.2.4.3.1.3 0 .8-.2 1.1z"/></svg>',
        'messenger' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3C6.8 3 3 6.6 3 11.2c0 2.6 1.3 4.9 3.3 6.4V21l3-1.6c.9.2 1.8.4 2.7.4 5.2 0 9-3.6 9-8.2S17.2 3 12 3zm1 11.1-2.3-2.4-4.4 2.4 4.8-5.1 2.3 2.4 4.4-2.4-4.8 5.1z"/></svg>'
    );
    $channels = array();
    $whatsapp = site_whatsapp_href(isset($settings['whatsapp_number']) ? $settings['whatsapp_number'] : '');
    if ($whatsapp !== '') {
        $channels[] = array('key' => 'whatsapp', 'label' => 'WhatsApp', 'note' => 'No account needed', 'href' => $whatsapp, 'icon' => $icons['whatsapp']);
    }
    $viber = site_viber_href(isset($settings['viber_number']) ? $settings['viber_number'] : '');
    if ($viber !== '') {
        $channels[] = array('key' => 'viber', 'label' => 'Viber', 'note' => 'Stays open', 'href' => $viber, 'icon' => $icons['viber']);
    }
    $messenger = site_public_url(isset($settings['messenger_url']) ? $settings['messenger_url'] : '');
    if ($messenger !== '') {
        $channels[] = array('key' => 'messenger', 'label' => 'Messenger', 'note' => 'No account needed', 'href' => $messenger, 'icon' => $icons['messenger']);
    }
    return $channels;
}

function site_guest_chats($settings)
{
    $chats = array();
    foreach (site_chat_channels($settings) as $channel) {
        if ($channel['key'] === 'whatsapp' || $channel['key'] === 'messenger') {
            $chats[] = $channel;
        }
    }
    return $chats;
}

function site_public_file($path, $pattern)
{
    $path = str_replace('\\', '/', (string) $path);
    if (!preg_match($pattern, $path)) {
        return '';
    }
    $full = dirname(__DIR__, 2) . '/' . $path;
    return is_file($full) ? $path : '';
}

function site_logo_file($path)
{
    return site_public_file($path, '/^uploads\/site-logo\.(png|jpe?g|webp|gif)$/');
}

function site_notice_file($path)
{
    return site_public_file($path, '/^uploads\/site-notice\.(png|jpe?g|webp|gif)$/');
}

function site_logo_web_path($path)
{
    $path = site_logo_file($path);
    if ($path === '') {
        return '';
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $prefix = (strpos($script, '/admin/') !== false || strpos($script, '/client/') !== false) ? '../' : '';
    return $prefix . $path;
}

function site_portal_identity($conn)
{
    $brand = site_public_settings($conn);
    $name = isset($brand['site_name']) ? trim((string) $brand['site_name']) : '';
    if ($name === '') {
        $name = 'Aakash Technologies';
    }
    $letter = strtoupper(substr($name, 0, 1));
    $logo = '';
    if (isset($brand['logo_path'])) {
        $logo = site_logo_web_path($brand['logo_path']);
    }
    return array(
        'name' => $name,
        'logo' => $logo,
        'letter' => $letter !== '' ? $letter : 'A'
    );
}

function site_poster_file($path)
{
    $path = str_replace('\\', '/', (string) $path);
    if (!preg_match('/^uploads\/service-poster-[a-z0-9-]+\.(png|jpe?g|webp|gif)$/', $path)) {
        return '';
    }
    $full = dirname(__DIR__, 2) . '/' . $path;
    return is_file($full) ? $path : '';
}

function site_poster_web_path($path)
{
    $path = site_poster_file($path);
    if ($path === '') {
        return '';
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $prefix = (strpos($script, '/admin/') !== false || strpos($script, '/client/') !== false) ? '../' : '';
    return $prefix . $path;
}

function site_service_poster($conn, $slug)
{
    $definitions = billing_service_definitions();
    if (!isset($definitions[$slug]) || !$conn) {
        return '';
    }
    try {
        $stored = billing_setting($conn, 'service_poster_' . $slug);
    } catch (Throwable $exception) {
        return '';
    }
    return site_poster_web_path($stored);
}

function site_store_poster($file, $slug)
{
    $definitions = billing_service_definitions();
    if (!isset($definitions[$slug])) {
        return array('ok' => false, 'error' => 'That service is not on the public site.');
    }
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The photo could not be uploaded.');
    }
    if ((int) $file['size'] > 4194304) {
        return array('ok' => false, 'error' => 'Use a photo smaller than 4 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $image = @getimagesize($file['tmp_name']);
    $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $imageTypes[IMAGETYPE_WEBP] = 'webp';
    }
    if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
        return array('ok' => false, 'error' => 'The photo must be a PNG, JPG, WEBP, or GIF image.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a photo no larger than 4000 pixels on a side.');
    }
    $dir = dirname(__DIR__, 2) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/service-poster-' . $slug . '.' . $imageTypes[$image[2]];
    $target = dirname(__DIR__, 2) . '/' . $relative;
    foreach (glob($dir . '/service-poster-' . $slug . '.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The photo could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function site_store_logo($file)
{
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The logo could not be uploaded.');
    }
    if ((int) $file['size'] > 2097152) {
        return array('ok' => false, 'error' => 'Use a logo smaller than 2 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $image = @getimagesize($file['tmp_name']);
    $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $imageTypes[IMAGETYPE_WEBP] = 'webp';
    }
    if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
        return array('ok' => false, 'error' => 'Logo must be a PNG, JPG, WEBP, or GIF image.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a logo no larger than 4000 pixels on a side.');
    }
    $types[$mime] = $imageTypes[$image[2]];
    $dir = dirname(__DIR__, 2) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/site-logo.' . $types[$mime];
    $target = dirname(__DIR__, 2) . '/' . $relative;
    foreach (glob($dir . '/site-logo.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The logo could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function site_store_notice_image($file)
{
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The notice image could not be uploaded.');
    }
    if ((int) $file['size'] > 2097152) {
        return array('ok' => false, 'error' => 'Use a notice image smaller than 2 MB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif');
    $image = @getimagesize($file['tmp_name']);
    $imageTypes = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif');
    if (defined('IMAGETYPE_WEBP')) {
        $imageTypes[IMAGETYPE_WEBP] = 'webp';
    }
    if (!isset($types[$mime]) || !$image || !isset($imageTypes[$image[2]]) || $types[$mime] !== $imageTypes[$image[2]]) {
        return array('ok' => false, 'error' => 'The notice image must be a PNG, JPG, WEBP, or GIF.');
    }
    if ((int) $image[0] < 1 || (int) $image[1] < 1 || (int) $image[0] > 4000 || (int) $image[1] > 4000) {
        return array('ok' => false, 'error' => 'Use a notice image no larger than 4000 pixels on a side.');
    }
    $types[$mime] = $imageTypes[$image[2]];
    $dir = dirname(__DIR__, 2) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/site-notice.' . $types[$mime];
    $target = dirname(__DIR__, 2) . '/' . $relative;
    foreach (glob($dir . '/site-notice.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return array('ok' => false, 'error' => 'The notice image could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

function site_favicon_file($path)
{
    return site_public_file($path, '/^uploads\/site-favicon\.(png|jpe?g|webp|ico)$/');
}

/**
 * The small picture in the browser tab. Admin uploads it under Settings. PNG, JPG, WEBP or ICO,
 * roughly square. SVG is not accepted because a file opened directly could carry a script.
 */
function site_store_favicon($file)
{
    if (!is_array($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'path' => null);
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'The tab icon could not be uploaded.');
    }
    if ((int) $file['size'] > 524288) {
        return array('ok' => false, 'error' => 'Use a tab icon smaller than 500 KB.');
    }
    $mime = '';
    if (class_exists('finfo')) {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $info->file($file['tmp_name']);
    }
    $ext = '';
    if (in_array($mime, array('image/x-icon', 'image/vnd.microsoft.icon'), true)) {
        $head = (string) @file_get_contents($file['tmp_name'], false, null, 0, 4);
        if ($head !== "\x00\x00\x01\x00") {
            return array('ok' => false, 'error' => 'That is not a real ICO file.');
        }
        $ext = 'ico';
    } else {
        $types = array('image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp');
        $image = @getimagesize($file['tmp_name']);
        $byType = array(IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg');
        if (defined('IMAGETYPE_WEBP')) {
            $byType[IMAGETYPE_WEBP] = 'webp';
        }
        if (!isset($types[$mime]) || !$image || !isset($byType[$image[2]]) || $types[$mime] !== $byType[$image[2]]) {
            return array('ok' => false, 'error' => 'The tab icon must be a PNG, JPG, WEBP or ICO image.');
        }
        $w = (int) $image[0];
        $h = (int) $image[1];
        if ($w < 32 || $h < 32) {
            return array('ok' => false, 'error' => 'The tab icon is too small. Use at least 64 by 64 pixels (512 by 512 is best).');
        }
        if ($w > 2000 || $h > 2000 || max($w, $h) / min($w, $h) > 1.5) {
            return array('ok' => false, 'error' => 'Use a square image no larger than 2000 pixels. A wide logo becomes unreadable in a tab.');
        }
        $ext = $types[$mime];
    }
    $dir = dirname(__DIR__, 2) . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return array('ok' => false, 'error' => 'The uploads folder could not be created.');
    }
    $relative = 'uploads/site-favicon.' . $ext;
    foreach (glob($dir . '/site-favicon.*') as $old) {
        if (is_file($old)) {
            unlink($old);
        }
    }
    $target = dirname(__DIR__, 2) . '/' . $relative;
    $moved = !empty($GLOBALS['KYC_TEST_UPLOADS']) ? copy($file['tmp_name'], $target) : move_uploaded_file($file['tmp_name'], $target);
    if (!$moved) {
        return array('ok' => false, 'error' => 'The tab icon could not be saved.');
    }
    return array('ok' => true, 'path' => $relative);
}

/** <link> tags for the tab icon. With no icon uploaded the logo is used, so a tab is never blank. */
function site_favicon_html($conn, $prefix = '')
{
    static $cache = array();
    if (isset($cache[$prefix])) {
        return $cache[$prefix];
    }
    $cache[$prefix] = '';
    if (!$conn) {
        return '';
    }
    $settings = site_public_settings($conn);
    $path = site_favicon_file(isset($settings['favicon_path']) ? $settings['favicon_path'] : '');
    if ($path === '') {
        $path = site_logo_file(isset($settings['logo_path']) ? $settings['logo_path'] : '');
    }
    if ($path === '') {
        // No icon uploaded yet: an empty icon link stops the browser requesting /favicon.ico and getting a 404.
        $cache[$prefix] = '<link rel="icon" href="data:,">';
        return $cache[$prefix];
    }
    $full = dirname(__DIR__, 2) . '/' . $path;
    $version = is_file($full) ? (int) filemtime($full) : 0;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif', 'ico' => 'image/x-icon');
    $href = htmlspecialchars($prefix . $path . '?v=' . $version, ENT_QUOTES, 'UTF-8');
    $html = '<link rel="icon" type="' . $types[$ext] . '" href="' . $href . '">';
    if (in_array($ext, array('png', 'jpg', 'jpeg', 'webp'), true)) {
        $html .= '<link rel="apple-touch-icon" href="' . $href . '">';
    }
    $cache[$prefix] = $html;
    return $html;
}

/** The company facts a customer looks for before trusting a site. Only what was filled in; nothing is invented. */
function site_company_facts($settings)
{
    $facts = array();
    foreach (array('company_legal_name' => 'Registered as', 'company_registration' => 'Registration no.', 'company_pan' => 'PAN / VAT no.', 'office_hours' => 'Office hours') as $key => $label) {
        $value = isset($settings[$key]) ? trim((string) $settings[$key]) : '';
        if ($value !== '') {
            $facts[] = array('label' => $label, 'value' => $value);
        }
    }
    return $facts;
}
