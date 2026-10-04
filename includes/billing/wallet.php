<?php
/**
 * Billing: Wallet, SMS/voice units, ledger, top-ups and the all-or-nothing transaction helper.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_payment_methods($conn = null)
{
    $esewa = (defined('ESEWA_ID') && ESEWA_ID !== '') ? ESEWA_ID : '';
    $khalti = (defined('KHALTI_ID') && KHALTI_ID !== '') ? KHALTI_ID : '';
    $bank = (defined('BANK_DETAILS') && BANK_DETAILS !== '') ? BANK_DETAILS : '';
    if ($conn) {
        $settings = site_public_settings($conn);
        if (isset($settings['esewa_id']) && trim((string) $settings['esewa_id']) !== '') {
            $esewa = trim((string) $settings['esewa_id']);
        }
        if (isset($settings['khalti_id']) && trim((string) $settings['khalti_id']) !== '') {
            $khalti = trim((string) $settings['khalti_id']);
        }
        if (isset($settings['bank_details']) && trim((string) $settings['bank_details']) !== '') {
            $bank = trim((string) $settings['bank_details']);
        }
    }
    $methods = array();
    if ($esewa !== '') {
        $methods[] = array(
            'code' => 'esewa',
            'group' => 'online',
            'label' => 'eSewa',
            'detail' => $esewa,
            'instruction' => 'Pay with eSewa to ' . $esewa . ', then enter the transaction code.'
        );
    }
    if ($khalti !== '') {
        $methods[] = array(
            'code' => 'khalti',
            'group' => 'online',
            'label' => 'Khalti',
            'detail' => $khalti,
            'instruction' => 'Pay with Khalti to ' . $khalti . ', then enter the transaction code.'
        );
    }
    if ($bank !== '') {
        $methods[] = array(
            'code' => 'bank',
            'group' => 'manual',
            'label' => 'Bank transfer',
            'detail' => $bank,
            'instruction' => 'Transfer to this account, then enter the voucher or reference number. ' . $bank
        );
    }
    return $methods;
}

function billing_ensure_wallet($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT client_id FROM client_wallets WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if ($row) {
        return;
    }
    $stmt = $conn->prepare('INSERT INTO client_wallets (client_id, balance) VALUES (?, 0)');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $stmt->close();
}

function billing_balance($conn, $clientId)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT balance FROM client_wallets WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? (float) $row['balance'] : 0.0;
}

/**
 * All-or-nothing money operations. Nested calls join the outer transaction,
 * so a wallet debit, the service row and the SMS credits either all happen or none do.
 */
function billing_tx($conn, $step)
{
    static $depth = 0;
    try {
        if ($step === 'begin') {
            if ($depth === 0) {
                if (DB_DRIVER === 'sqlite') {
                    $conn->query('BEGIN');
                } else {
                    $conn->begin_transaction();
                }
            }
            $depth++;
            return true;
        }
        if ($depth === 0) {
            return true;
        }
        $depth--;
        if ($step === 'commit' && $depth > 0) {
            return true;
        }
        if ($step === 'rollback') {
            $depth = 0;
        }
        if (DB_DRIVER === 'sqlite') {
            $conn->query($step === 'commit' ? 'COMMIT' : 'ROLLBACK');
        } elseif ($step === 'commit') {
            $conn->commit();
        } else {
            $conn->rollback();
        }
        return true;
    } catch (Throwable $exception) {
        $depth = 0;
        return false;
    }
}

/**
 * E-mail a client once a day when their SMS credits fall below the warning level.
 * Never blocks or breaks a send: any problem here is swallowed.
 */
function billing_low_sms_alert($conn, $clientId, $threshold = 100)
{
    try {
        $clientId = (int) $clientId;
        $left = (int) billing_unit_balances($conn, $clientId)['sms'];
        if ($clientId < 1 || $left >= (int) $threshold) {
            return false;
        }
        $key = 'sms_low_alert_' . $clientId;
        $today = date('Y-m-d');
        if (billing_setting($conn, $key) === $today) {
            return false;
        }
        billing_set_setting($conn, $key, $today);
        billing_mail_client_event($conn, $clientId, 'sms-low', array('left' => number_format($left)));
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

function billing_wallet_debit($conn, $clientId, $amount)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    if ((float) $amount <= 0) {
        return true;
    }
    $stmt = $conn->prepare('UPDATE client_wallets SET balance = balance - ? WHERE client_id = ? AND balance >= ?');
    $stmt->bind_param('sis', $amount, $clientId, $amount);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_wallet_credit($conn, $clientId, $amount)
{
    billing_ensure_wallet($conn, $clientId);
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    $stmt = $conn->prepare('UPDATE client_wallets SET balance = balance + ? WHERE client_id = ?');
    $stmt->bind_param('si', $amount, $clientId);
    $stmt->execute();
    $stmt->close();
}

function billing_add_units($conn, $clientId, $kind, $quantity)
{
    if (($kind !== 'sms' && $kind !== 'voice_minutes' && $kind !== 'voice_calls') || $quantity <= 0) {
        return;
    }
    $clientId = (int) $clientId;
    $quantity = (int) $quantity;
    $stmt = $conn->prepare('SELECT balance FROM client_units WHERE client_id = ? AND unit_kind = ?');
    $stmt->bind_param('is', $clientId, $kind);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if ($row) {
        $stmt = $conn->prepare('UPDATE client_units SET balance = balance + ? WHERE client_id = ? AND unit_kind = ?');
        $stmt->bind_param('iis', $quantity, $clientId, $kind);
        $stmt->execute();
        $stmt->close();
        return;
    }
    $stmt = $conn->prepare('INSERT INTO client_units (client_id, unit_kind, balance) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $clientId, $kind, $quantity);
    $stmt->execute();
    $stmt->close();
}

function billing_take_units($conn, $clientId, $kind, $quantity)
{
    if (($kind !== 'sms' && $kind !== 'voice_minutes' && $kind !== 'voice_calls') || (int) $quantity < 1) {
        return false;
    }
    $clientId = (int) $clientId;
    $quantity = (int) $quantity;
    $stmt = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iisi', $quantity, $clientId, $kind, $quantity);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function billing_unit_balances($conn, $clientId)
{
    $balances = array('sms' => 0, 'voice_minutes' => 0, 'voice_calls' => 0);
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT unit_kind, balance FROM client_units WHERE client_id = ?');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    foreach (db_fetch_all($stmt) as $row) {
        $balances[(string) $row['unit_kind']] = (int) $row['balance'];
    }
    $stmt->close();
    return $balances;
}

function billing_record_entry($conn, $clientId, $amount, $direction, $kind, $status, $method, $note, $serviceId)
{
    $clientId = (int) $clientId;
    $amount = billing_money($amount);
    $serviceId = (int) $serviceId;
    $created = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO wallet_entries (client_id, amount, direction, kind, status, method, reference_note, related_service_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssis', $clientId, $amount, $direction, $kind, $status, $method, $note, $serviceId, $created);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    return $id;
}

function billing_request_topup($conn, $clientId, $amount, $method, $reference)
{
    $allowed = array();
    foreach (billing_payment_methods($conn) as $methodRow) {
        $allowed[] = $methodRow['code'];
    }
    $amount = (int) $amount;
    $reference = trim((string) $reference);
    if (!in_array($method, $allowed, true)) {
        return 'Choose one of the payment methods saved for this site.';
    }
    if ($amount < 100 || $amount > 1000000) {
        return 'Enter an amount between NPR 100 and NPR 1,000,000.';
    }
    if (strlen($reference) < 4 || strlen($reference) > 80) {
        return 'Enter the payment reference (4–80 characters).';
    }
    billing_record_entry($conn, (int) $clientId, $amount, 'credit', 'topup', 'pending', $method, $reference, 0);
    billing_notify($conn, 'Wallet top-up waiting', array(
        'A wallet top-up is waiting for confirmation.',
        'Amount: NPR ' . number_format($amount),
        'Method: ' . $method,
        'Reference: ' . billing_notify_clip($reference, 80),
        'Client: ' . billing_notify_client_label($conn, $clientId),
        'Open Admin → Billing and confirm it. Later renewals do not need this step.'
    ));
    billing_mail_client_event($conn, (int) $clientId, 'topup-waiting', array('amount' => number_format($amount)));
    return '';
}

function billing_approve_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("SELECT id, client_id, amount FROM wallet_entries WHERE id = ? AND kind = 'topup' AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$entry) {
        return false;
    }
    billing_tx($conn, 'begin');
    try {
        $stmt = $conn->prepare("UPDATE wallet_entries SET status = 'completed' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $entryId);
        $stmt->execute();
        $changed = billing_affected($conn) === 1;
        $stmt->close();
        if (!$changed) {
            billing_tx($conn, 'rollback');
            return false;
        }
        billing_wallet_credit($conn, (int) $entry['client_id'], $entry['amount']);
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        return false;
    }
    billing_tx($conn, 'commit');
    billing_mail_client_event($conn, (int) $entry['client_id'], 'topup-done', array(
        'amount' => number_format((float) $entry['amount'])
    ));
    return true;
}

function billing_admin_wallet_credit($conn, $clientId, $amount, $note)
{
    $clientId = (int) $clientId;
    $amount = (int) $amount;
    $note = billing_plain_line($note, 160);
    if ($clientId < 1) {
        return 'Choose a client.';
    }
    if ($amount < 1 || $amount > 1000000) {
        return 'Enter an amount from NPR 1 to NPR 1,000,000.';
    }
    if ($note === '') {
        return 'Write where this payment was received, such as cash at the office.';
    }
    $check = $conn->prepare('SELECT id FROM client_users WHERE id = ?');
    $check->bind_param('i', $clientId);
    $check->execute();
    $client = db_fetch_assoc($check);
    $check->close();
    if (!$client) {
        return 'That client was not found.';
    }
    $method = 'office';
    billing_tx($conn, 'begin');
    try {
        billing_record_entry($conn, $clientId, $amount, 'credit', 'topup', 'completed', $method, $note, 0);
        billing_wallet_credit($conn, $clientId, $amount);
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        return 'The payment could not be saved. Nothing was added.';
    }
    billing_tx($conn, 'commit');
    billing_mail_client_event($conn, $clientId, 'office-wallet', array(
        'amount' => number_format($amount),
        'note' => $note
    ));
    return '';
}

function billing_admin_wallet_reverse($conn, $clientId, $entryId)
{
    $clientId = (int) $clientId;
    $entryId = (int) $entryId;
    if ($clientId < 1 || $entryId < 1) {
        return 'That payment was not found.';
    }
    $stmt = $conn->prepare('SELECT id, amount, direction, kind, status, method FROM wallet_entries WHERE id = ? AND client_id = ?');
    if (!$stmt) {
        return 'That payment could not be read.';
    }
    $stmt->bind_param('ii', $entryId, $clientId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$entry || $entry['kind'] !== 'topup' || $entry['method'] !== 'office' || $entry['direction'] !== 'credit' || $entry['status'] !== 'completed') {
        return 'Only an office payment that is still in the wallet can be taken back.';
    }
    $amount = billing_money($entry['amount']);
    if ((float) $amount <= 0) {
        return 'That payment has no amount to take back.';
    }
    $reversed = 'reversed';
    $completed = 'completed';
    $mark = $conn->prepare('UPDATE wallet_entries SET status = ? WHERE id = ? AND client_id = ? AND status = ?');
    if (!$mark) {
        return 'That payment could not be taken back.';
    }
    $mark->bind_param('siis', $reversed, $entryId, $clientId, $completed);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        return 'That payment was already taken back.';
    }
    if (!billing_wallet_debit($conn, $clientId, $amount)) {
        $restore = $conn->prepare('UPDATE wallet_entries SET status = ? WHERE id = ? AND client_id = ?');
        if ($restore) {
            $restore->bind_param('sii', $completed, $entryId, $clientId);
            $restore->execute();
            $restore->close();
        }
        $left = billing_balance($conn, $clientId);
        return 'The wallet has NPR ' . number_format((float) $left, 2) . ' left. This payment is NPR ' . number_format((float) $amount, 2) . ', and some of it was already spent, so it cannot be taken back.';
    }
    return '';
}

function billing_reject_topup($conn, $entryId)
{
    $entryId = (int) $entryId;
    $stmt = $conn->prepare("SELECT client_id, amount FROM wallet_entries WHERE id = ? AND kind = 'topup' AND status = 'pending'");
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$entry) {
        return false;
    }
    $update = $conn->prepare("UPDATE wallet_entries SET status = 'rejected' WHERE id = ? AND status = 'pending'");
    $update->bind_param('i', $entryId);
    $update->execute();
    $changed = billing_affected($conn) === 1;
    $update->close();
    if ($changed) {
        billing_mail_client_event($conn, (int) $entry['client_id'], 'topup-rejected', array(
            'amount' => number_format((float) $entry['amount'])
        ));
    }
    return $changed;
}
