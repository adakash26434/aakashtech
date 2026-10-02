<?php
require_once __DIR__ . '/../config.php';
require_client();
$slot = isset($_GET['slot']) ? (string) $_GET['slot'] : '';
billing_kyc_send($conn, (int) get_client_id(), $slot);
