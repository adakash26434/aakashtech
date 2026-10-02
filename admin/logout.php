<?php
require_once __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    header('Location: index.php');
    exit;
}
admin_logout();
