<?php
// Daily database backup. cPanel cron, once a day:
//   php /home/USER/public_html/cron/backup.php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/backup.php';

if (PHP_SAPI !== 'cli') {
    $expected = defined('CRON_KEY') ? (string) CRON_KEY : '';
    $given = isset($_GET['key']) ? (string) $_GET['key'] : '';
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

$cronLock = fopen(sys_get_temp_dir() . '/aakash-backup.lock', 'c');
if ($cronLock === false || !flock($cronLock, LOCK_EX | LOCK_NB)) {
    echo "Already running\n";
    exit;
}

$result = backup_run($conn, 14);
if ($result['ok']) {
    echo 'Backup saved: ' . $result['file'] . ' (' . $result['rows'] . ' rows, ' . round($result['bytes'] / 1024) . " KB)\n";
} else {
    http_response_code(500);
    echo 'Backup failed: ' . $result['message'] . "\n";
    exit(1);
}
