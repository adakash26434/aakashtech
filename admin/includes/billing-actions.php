<?php
/**
 * Admin Billing: handles the form posts and prepares the values the page shows.
 * Included by admin/billing.php, so it shares that page's variables ($conn, $msg, $err ...).
 */

$pending = $conn->query("SELECT w.*, c.name, c.email FROM wallet_entries w JOIN client_users c ON c.id = w.client_id WHERE w.kind = 'topup' AND w.status = 'pending' ORDER BY w.id ASC");
$walletClients = $conn->query('SELECT id, name, email FROM client_users ORDER BY name ASC LIMIT 2000');
$officePayments = $conn->query("SELECT w.amount, w.reference_note, w.created_at, w.client_id, c.name, c.email FROM wallet_entries w JOIN client_users c ON c.id = w.client_id WHERE w.kind = 'topup' AND w.method = 'office' AND w.status = 'completed' ORDER BY w.id DESC LIMIT 20");
$plans = billing_load_plans($conn);
$smsSlabs = billing_load_slabs($conn, 'bulk-sms');
$voiceSlabs = billing_load_slabs($conn, 'bulk-voice');
$subscriptions = $conn->query("SELECT s.*, c.name, c.email FROM client_services s JOIN client_users c ON c.id = s.client_id WHERE s.plan_code IS NOT NULL AND s.plan_code != '' ORDER BY s.id DESC LIMIT 30");
$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$hostingLogins = hosting_admin_rows($conn, $find);
$mailLogins = mail_admin_rows($conn, $find);
$websiteJobs = website_admin_rows($conn, $find);
$trainingJobs = training_admin_rows($conn, $find);
$renewals = $conn->query('SELECT * FROM renewal_events ORDER BY id DESC LIMIT 12');
