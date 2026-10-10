<?php
/**
 * Admin SMS line: handles the form posts and prepares the values the page shows.
 * Included by admin/sms-line.php, so it shares that page's variables ($conn, $msg, $err ...).
 */

$line = array('provider' => '', 'sender' => '', 'sender_mode' => 'fixed', 'connected' => false);
$storedKey = '';
$tokenSet = false;
$tokenTail = '';
$endpoint = '';
$vendorLabel = '';
$senders = false;
$usageFind = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$historyClient = isset($_GET['client']) ? (int) $_GET['client'] : 0;
$historyStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
$usage = array('rows' => array(), 'clients' => 0, 'used' => 0, 'left' => 0);
$history = array();
$vendorStock = array('balance' => null, 'checked' => '', 'error' => '', 'label' => '');
$clientsHolding = 0;
$grantClients = false;
$creditNotes = false;
$pendingNames = 0;
$grantPick = isset($_GET['grant']) ? (int) $_GET['grant'] : 0;
try {
    sms_credit_columns($conn);
    $line = sms_line($conn);
    $storedKey = (string) sms_line_secret($conn)['token'];
    $tokenSet = $storedKey !== '';
    $tokenTail = $tokenSet ? substr($storedKey, -4) : '';
    $endpoint = billing_setting($conn, 'sms_line_endpoint');
    $vendorLabel = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
    $senders = $conn->query("SELECT s.*, c.name AS client_name, c.email AS client_email FROM sms_sender_names s JOIN client_users c ON c.id = s.client_id ORDER BY CASE WHEN s.status = 'pending' THEN 0 ELSE 1 END, s.id DESC LIMIT 50");
    $usage = sms_admin_usage($conn, $usageFind);
    $history = sms_admin_history($conn, $historyClient, $usageFind, $historyStatus);
    $vendorStock = sms_vendor_stock($conn, false);
    $clientsHolding = sms_clients_holding($conn);
    $grantClients = $conn->query("SELECT c.id, c.name, c.email, COALESCE(u.balance, 0) AS sms_left FROM client_users c LEFT JOIN client_units u ON u.client_id = c.id AND u.unit_kind = 'sms' ORDER BY c.name ASC LIMIT 2000");
    $pendingResult = $conn->query("SELECT COUNT(*) AS c FROM sms_sender_names WHERE status = 'pending'");
    $pendingRow = $pendingResult ? $pendingResult->fetch_assoc() : null;
    $pendingNames = $pendingRow ? (int) $pendingRow['c'] : 0;
    $creditNotes = $conn->query('SELECT n.id, n.credits, n.note, n.created_at, n.reversed_at, c.id AS client_id, c.name, c.email FROM sms_credit_notes n JOIN client_users c ON c.id = n.client_id ORDER BY n.id DESC LIMIT 40');
} catch (Throwable $exception) {
    error_log('SMS line page could not be loaded.');
    if ($err === '') {
        $err = 'The SMS line page could not load every figure. Refresh once.';
    }
}
