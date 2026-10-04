<?php
/**
 * Copy this file to cpanel-config.local.php on the server and fill in the real values.
 * cpanel-config.local.php is ignored by Git, so passwords are never pushed.
 * Values in the local file win over cpanel-config.php.
 */
return array(
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',

    'admin_email' => '',
    'admin_password' => '',   // at least 8 characters

    // Public address of the site (no trailing slash). Used in e-mailed links.
    'site_url' => 'https://aakashtechnologies.com.np',

    // Optional: AES key for stored hosting passwords, 64 hex characters.
    // If you set this on a live site, copy the existing key from the
    // settings table (panel_cipher_key) first, or old passwords will not open.
    // Generate a new one with: php -r "echo bin2hex(random_bytes(32));"
    'cipher_key' => '',

    'cron_key' => '',

    // Where daily database backups go. Empty = a folder next to the site (outside the web root) is used.
    'backup_dir' => '',

    // Only if the site is behind Cloudflare/another proxy: the header carrying the visitor's real IP.
    // One of: CF-Connecting-IP, X-Real-IP, X-Forwarded-For. Leave empty otherwise.
    'ip_header' => '',

    // Secret for SMS delivery reports. Provider callback: https://YOUR-SITE/api/sms-dlr.php?key=THIS_VALUE
    // Generate one with: php -r "echo bin2hex(random_bytes(16));"
    'dlr_key' => '',
);
