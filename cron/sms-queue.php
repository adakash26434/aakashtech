<?php

require_once dirname(__DIR__) . '/config.php';

if (PHP_SAPI !== 'cli') {
    $expected = defined('CRON_KEY') ? (string) CRON_KEY : '';
    $given = isset($_GET['key']) ? (string) $_GET['key'] : '';
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

// One run at a time: a slow run must not overlap the next cron tick (double charges / double SMS).
$cronLock = fopen(sys_get_temp_dir() . '/aakash-' . basename(__FILE__, '.php') . '.lock', 'c');
if ($cronLock === false || !flock($cronLock, LOCK_EX | LOCK_NB)) {
    echo "Already running\n";
    exit;
}

sms_run_queue($conn, 20, 50);
echo "SMS queue checked\n";
