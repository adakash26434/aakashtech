<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/domain-check.php';
require_admin();
domain_send_file($conn, isset($_GET['id']) ? (int) $_GET['id'] : 0, 0);
