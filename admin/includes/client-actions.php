<?php
/**
 * Admin Client detail: handles the form posts and prepares the values the page shows.
 * Included by admin/client.php, so it shares that page's variables ($conn, $msg, $err ...).
 */

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$back = 'clients.php';
$goTab = 'account';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    verify_csrf();
    if (isset($_POST['toggle_client'])) {
        // Only the change the button asked for is applied, and only if the account is still in the
        // state the page showed. A stale page cannot re-activate a suspended client.
        $suspend = (isset($_POST['target']) ? $_POST['target'] : '') !== 'activate';
        $from = $suspend ? 'active' : 'suspended';
        $to = $suspend ? 'suspended' : 'active';
        $stmt = $conn->prepare('UPDATE client_users SET status = ? WHERE id = ? AND status = ?');
        $stmt->bind_param('sis', $to, $id, $from);
        $stmt->execute();
        $changed = (int) $conn->affected_rows === 1;
        $stmt->close();
        flash('client_notice', $changed ? 'Account status updated.' : 'That account was already changed. Refresh the page and check its status.');
    } elseif (isset($_POST['reset_authenticator'])) {
        totp_clear($conn, 'client', $id);
        flash('client_notice', 'Authenticator reset. The client sets it up again on the next sign-in.');
    } elseif (isset($_POST['open_portal'])) {
        $opened = client_office_open($conn, $id);
        if ($opened === '') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Location: ../client/index.php');
            exit;
        }
        flash('client_error', $opened);
    } elseif (isset($_POST['email_reset'])) {
        $lookup = $conn->prepare('SELECT email, status FROM client_users WHERE id = ? LIMIT 1');
        $lookup->bind_param('i', $id);
        $lookup->execute();
        $resetRow = db_fetch_assoc($lookup);
        $lookup->close();
        if (!$resetRow || (string) $resetRow['status'] !== 'active') {
            flash('client_error', 'Activate the account before emailing a reset link.');
        } else {
            password_reset_request($conn, (string) $resetRow['email']);
            flash('client_notice', 'A reset link was emailed. It works for 30 minutes. The current password stays until they choose a new one.');
        }
    } elseif (isset($_POST['set_password'])) {
        $newPassword = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
        $again = isset($_POST['new_password_again']) ? (string) $_POST['new_password_again'] : '';
        if ($newPassword !== $again) {
            flash('client_error', 'The two passwords do not match.');
            $_SESSION['client_password_draft'] = array('id' => $id, 'one' => $newPassword, 'two' => $again);
        } else {
            $passwordError = client_admin_set_password($conn, $id, $newPassword);
            if ($passwordError === '') {
                flash('client_notice', 'Password saved. Tell the client: ' . $newPassword . '. It is shown once and is not written in the email.');
            } else {
                flash('client_error', $passwordError);
                $_SESSION['client_password_draft'] = array('id' => $id, 'one' => $newPassword, 'two' => $again);
            }
        }
    } elseif (isset($_POST['set_contact'])) {
        $contactError = billing_admin_set_contact(
            $conn,
            $id,
            isset($_POST['contact_email']) ? $_POST['contact_email'] : '',
            isset($_POST['contact_phone']) ? $_POST['contact_phone'] : ''
        );
        if ($contactError === '') {
            flash('client_notice', 'Email and mobile saved.');
        } else {
            flash('client_error', $contactError);
            $_SESSION['client_contact_draft'] = array(
                'id' => $id,
                'email' => isset($_POST['contact_email']) ? (string) $_POST['contact_email'] : '',
                'phone' => isset($_POST['contact_phone']) ? (string) $_POST['contact_phone'] : ''
            );
        }
    } elseif (isset($_POST['grant_sms'])) {
        $grantError = sms_admin_grant(
            $conn,
            $id,
            isset($_POST['grant_credits']) ? (int) $_POST['grant_credits'] : 0,
            isset($_POST['grant_note']) ? $_POST['grant_note'] : ''
        );
        $goTab = 'sms';
        if ($grantError === '') {
            flash('client_notice', 'SMS credits added. The client can send them from this site. Their wallet was not charged.');
        } else {
            flash('client_error', $grantError);
        }
    } elseif (isset($_POST['reverse_sms'])) {
        $goTab = 'sms';
        $reversed = sms_admin_reverse($conn, $id, isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0);
        if ($reversed['error'] === '') {
            flash('client_notice', $reversed['message']);
        } else {
            flash('client_error', $reversed['error']);
        }
    } elseif (isset($_POST['add_wallet'])) {
        $goTab = 'wallet';
        $walletError = billing_admin_wallet_credit(
            $conn,
            $id,
            isset($_POST['wallet_amount']) ? (int) $_POST['wallet_amount'] : 0,
            isset($_POST['wallet_note']) ? $_POST['wallet_note'] : ''
        );
        if ($walletError === '') {
            flash('client_notice', 'Payment added to the wallet. The client can spend it on services.');
        } else {
            flash('client_error', $walletError);
        }
    } elseif (isset($_POST['reverse_wallet'])) {
        $goTab = 'wallet';
        $walletError = billing_admin_wallet_reverse($conn, $id, isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0);
        if ($walletError === '') {
            flash('client_notice', 'Office payment taken back. That amount is no longer in the wallet.');
        } else {
            flash('client_error', $walletError);
        }
    } elseif (isset($_POST['add_service'])) {
        $goTab = 'services';
        $added = billing_admin_add_service(
            $conn,
            $id,
            isset($_POST['service_plan']) ? $_POST['service_plan'] : '',
            isset($_POST['service_quantity']) ? (int) $_POST['service_quantity'] : 0,
            isset($_POST['service_detail']) ? $_POST['service_detail'] : ''
        );
        if ($added === '') {
            flash('client_notice', 'Service added. The wallet was not charged. A yearly or monthly service still renews from the wallet later.');
        } else {
            flash('client_error', $added);
        }
    } elseif (isset($_POST['take_service'])) {
        $goTab = 'services';
        $taken = billing_admin_take_service($conn, $id, isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0);
        if ($taken['error'] === '') {
            flash('client_notice', $taken['message']);
        } else {
            flash('client_error', $taken['error']);
        }
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: client.php?id=' . $id . '&tab=' . $goTab);
    exit;
}

$client = null;
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM client_users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $client = db_fetch_assoc($stmt);
    $stmt->close();
}

$services = array();
$balance = '0.00';
$figures = array('used' => 0, 'left' => 0);
$units = array('sms' => 0, 'voice_calls' => 0);
$servicePlans = array();
$creditNotes = array();
$walletRows = array();
$bulkLeft = null;
$clientTab = 'account';
$loadProblem = '';
if (isset($_GET['tab']) && in_array($_GET['tab'], array('account', 'wallet', 'sms', 'services'), true)) {
    $clientTab = (string) $_GET['tab'];
}
if ($client) {
    try {
    $balance = billing_balance($conn, $id);
    $units = billing_unit_balances($conn, $id);
    $one = sms_admin_client_figures($conn, array($id));
    if (isset($one[$id])) {
        $figures = $one[$id];
    }
    $svc = $conn->prepare('SELECT id, service_name, status, detail_label, price, next_renewal, order_brief, unit_kind, unit_quantity FROM client_services WHERE client_id = ? ORDER BY id DESC LIMIT 40');
    if ($svc) {
    $svc->bind_param('i', $id);
    $svc->execute();
    $services = db_fetch_all($svc);
    $svc->close();
    }
    $planResult = $conn->query('SELECT code, name, needs_detail, service_slug FROM service_plans WHERE is_active = 1 ORDER BY sort_order, id');
    if ($planResult) {
        while ($planRow = $planResult->fetch_assoc()) {
            if ((string) $planRow['needs_detail'] === 'sms') {
                continue;
            }
            $servicePlans[] = $planRow;
        }
    }
    $noteStmt = $conn->prepare('SELECT id, credits, note, created_at, reversed_at FROM sms_credit_notes WHERE client_id = ? ORDER BY id DESC LIMIT 12');
    if ($noteStmt) {
        $noteStmt->bind_param('i', $id);
        $noteStmt->execute();
        $creditNotes = db_fetch_all($noteStmt);
        $noteStmt->close();
    }
    $bulk = sms_vendor_stock_saved($conn);
    $bulkLeft = $bulk['balance'];
    $walletStmt = $conn->prepare('SELECT id, amount, direction, kind, status, method, reference_note, created_at FROM wallet_entries WHERE client_id = ? ORDER BY id DESC LIMIT 12');
    if ($walletStmt) {
        $walletStmt->bind_param('i', $id);
        $walletStmt->execute();
        $walletRows = db_fetch_all($walletStmt);
        $walletStmt->close();
    }
    } catch (Throwable $exception) {
        error_log('Client account page could not be loaded.');
        $loadProblem = 'This client could not be opened. Refresh the page.';
    }
}
$planGroups = array(
    'voice' => 'Voice calls',
    'domain' => 'Domain',
    'hosting' => 'Hosting',
    'email' => 'Email',
    'website' => 'Website',
    'training' => 'Training'
);
$planNeeds = array();
foreach ($servicePlans as $servicePlan) {
    $planNeeds[(string) $servicePlan['code']] = (string) $servicePlan['needs_detail'];
}

$notice = flash('client_notice');
$problem = flash('client_error');
if ($problem === '' && $loadProblem !== '') {
    $problem = $loadProblem;
}
$contactEmail = $client ? (string) $client['email'] : '';
$contactPhone = $client ? (string) $client['phone'] : '';
$passwordOne = '';
$passwordTwo = '';
if ($client && isset($_SESSION['client_contact_draft']) && is_array($_SESSION['client_contact_draft']) && (int) $_SESSION['client_contact_draft']['id'] === (int) $client['id']) {
    $contactEmail = (string) $_SESSION['client_contact_draft']['email'];
    $contactPhone = (string) $_SESSION['client_contact_draft']['phone'];
    unset($_SESSION['client_contact_draft']);
}
if ($client && isset($_SESSION['client_password_draft']) && is_array($_SESSION['client_password_draft']) && (int) $_SESSION['client_password_draft']['id'] === (int) $client['id']) {
    $passwordOne = (string) $_SESSION['client_password_draft']['one'];
    $passwordTwo = (string) $_SESSION['client_password_draft']['two'];
    unset($_SESSION['client_password_draft']);
}
