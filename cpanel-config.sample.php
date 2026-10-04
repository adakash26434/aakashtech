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

    // Secret for SMS delivery reports. Provider callback: https://YOUR-SITE/api/sms-dlr.php?key=THIS_VALUE
    // Generate one with: php -r "echo bin2hex(random_bytes(16));"
    'dlr_key' => '',
);
