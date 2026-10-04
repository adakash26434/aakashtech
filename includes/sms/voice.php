<?php
/**
 * SMS: Voice call jobs.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function voice_place_job($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT id, client_id, channel, status, recipients_count, message_content FROM sms_campaigns WHERE id = ?');
    $stmt->bind_param('i', $campaignId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['channel'] !== 'voice' || ((string) $row['status'] !== 'draft' && (string) $row['status'] !== 'scheduled')) {
        return 'That voice job cannot be marked placed.';
    }
    $count = (int) $row['recipients_count'];
    $clientId = (int) $row['client_id'];
    if ($count < 1 || $clientId < 1) {
        return 'This voice job has no numbers.';
    }
    $blocked = sms_prohibited_notice((string) $row['message_content']);
    if ($blocked !== '') {
        return $blocked;
    }
    $kind = 'voice_calls';
    $debit = $conn->prepare('UPDATE client_units SET balance = balance - ? WHERE client_id = ? AND unit_kind = ? AND balance >= ?');
    $debit->bind_param('iisi', $count, $clientId, $kind, $count);
    $debit->execute();
    $taken = billing_affected($conn) === 1;
    $debit->close();
    if (!$taken) {
        return 'This account does not have enough voice credits for these numbers.';
    }
    $status = 'sent';
    $mark = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND channel = \'voice\' AND status IN (\'draft\', \'scheduled\')');
    $mark->bind_param('si', $status, $campaignId);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        billing_add_units($conn, $clientId, $kind, $count);
        return 'That voice job could not be marked placed.';
    }
    return '';
}

function voice_cancel_job($conn, $clientId, $campaignId)
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $status = 'cancelled';
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND client_id = ? AND channel = \'voice\' AND status IN (\'draft\', \'scheduled\')');
    $stmt->bind_param('sii', $status, $campaignId, $clientId);
    $stmt->execute();
    $ok = billing_affected($conn) === 1;
    $stmt->close();
    return $ok;
}

function voice_reserved_count($conn, $clientId)
{
    $clientId = (int) $clientId;
    $channel = 'voice';
    $draft = 'draft';
    $scheduled = 'scheduled';
    $stmt = $conn->prepare('SELECT COALESCE(SUM(recipients_count), 0) AS held FROM sms_campaigns WHERE client_id = ? AND channel = ? AND status IN (?, ?)');
    $stmt->bind_param('isss', $clientId, $channel, $draft, $scheduled);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    return $row ? (int) $row['held'] : 0;
}

function voice_return_job($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT id, client_id, channel, status, recipients_count, scheduled_at FROM sms_campaigns WHERE id = ?');
    $stmt->bind_param('i', $campaignId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row || (string) $row['channel'] !== 'voice' || (string) $row['status'] !== 'sent') {
        return 'Those voice credits cannot be returned.';
    }
    $count = (int) $row['recipients_count'];
    $clientId = (int) $row['client_id'];
    if ($count < 1 || $clientId < 1) {
        return 'Those voice credits cannot be returned.';
    }
    $draft = trim((string) $row['scheduled_at']) !== '' ? 'scheduled' : 'draft';
    $sent = 'sent';
    $channel = 'voice';
    $mark = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND channel = ? AND status = ?');
    $mark->bind_param('siss', $draft, $campaignId, $channel, $sent);
    $mark->execute();
    $marked = billing_affected($conn) === 1;
    $mark->close();
    if (!$marked) {
        return 'Those voice credits cannot be returned.';
    }
    billing_add_units($conn, $clientId, 'voice_calls', $count);
    return '';
}
