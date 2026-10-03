<?php
require_once __DIR__ . '/../config.php';
require_client();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: services.php');
    exit;
}
verify_csrf();
$cid = (int) get_client_id();
$serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$row = website_for_client($conn, $cid, $serviceId);
$url = $row ? website_url($row) : '';
if ($url === '') {
    header('Location: services.php');
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('Location: ' . $url);
exit;
