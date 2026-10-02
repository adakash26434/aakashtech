<?php
require_once __DIR__ . '/../config.php';
require_admin();
$clientId = isset($_GET['client']) ? (int) $_GET['client'] : 0;
$slot = isset($_GET['slot']) ? (string) $_GET['slot'] : '';
billing_kyc_send($conn, $clientId, $slot);
