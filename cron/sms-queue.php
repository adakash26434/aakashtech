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

sms_run_queue($conn, 20);
echo "SMS queue checked\n";
