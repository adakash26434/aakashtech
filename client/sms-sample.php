<?php
require_once __DIR__ . '/../config.php';
require_client();

$kind = isset($_GET['type']) && $_GET['type'] === 'names' ? 'names' : 'mobile';
$file = sms_sample_workbook($kind);
if ($file['body'] === '') {
    http_response_code(500);
    echo 'The sample file could not be prepared.';
    exit;
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($file['body']));
echo $file['body'];
