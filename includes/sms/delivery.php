<?php
/**
 * SMS: Sending, scheduling, background queue and campaign state.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function sms_insert_campaign($conn, $clientId, $name, $text, $sender, $count, $status, $when, $audience, $purpose, $list)
{
    $channel = 'sms';
    $declaration = billing_use_declaration();
    $when = $when === null ? '' : (string) $when;
    $stmt = $conn->prepare('INSERT INTO sms_campaigns (client_id, campaign_name, message_content, sender_id, recipients_count, status, scheduled_at, channel, audience, purpose, recipients_list, declaration_text) VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssisssssss', $clientId, $name, $text, $sender, $count, $status, $when, $channel, $audience, $purpose, $list, $declaration);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    return $id;
}

function sms_insert_messages($conn, $clientId, $campaignId, $tokenId, $source, $sender, $text, $parts, $numbers)
{
    $status = 'queued';
    $error = '';
    $recipient = '';
    $stmt = $conn->prepare('INSERT INTO sms_messages (client_id, campaign_id, token_id, source, sender_id, recipient, message_text, parts, status, error_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissssiss', $clientId, $campaignId, $tokenId, $source, $sender, $recipient, $text, $parts, $status, $error);
    $ids = array();
    foreach ($numbers as $number) {
        $recipient = $number;
        $stmt->execute();
        $ids[] = (int) $conn->insert_id;
    }
    $stmt->close();
    return $ids;
}

function sms_db_batch($conn, $step)
{
    // Same nesting-aware transaction as the money code, so a batch inside a purchase never commits early.
    return billing_tx($conn, $step);
}

function sms_insert_rendered($conn, $clientId, $campaignId, $tokenId, $source, $sender, $messages)
{
    $batched = count($messages) > 1 && sms_db_batch($conn, 'begin');
    try {
        $ids = sms_insert_rendered_rows($conn, $clientId, $campaignId, $tokenId, $source, $sender, $messages);
    } catch (Throwable $exception) {
        if ($batched) {
            sms_db_batch($conn, 'rollback');
        }
        throw $exception;
    }
    if ($batched) {
        sms_db_batch($conn, 'commit');
    }
    return $ids;
}

function sms_insert_rendered_rows($conn, $clientId, $campaignId, $tokenId, $source, $sender, $messages)
{
    $status = 'queued';
    $error = '';
    $recipient = '';
    $text = '';
    $parts = 1;
    $stmt = $conn->prepare('INSERT INTO sms_messages (client_id, campaign_id, token_id, source, sender_id, recipient, message_text, parts, status, error_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissssiss', $clientId, $campaignId, $tokenId, $source, $sender, $recipient, $text, $parts, $status, $error);
    $ids = array();
    foreach ($messages as $message) {
        $recipient = $message['number'];
        $text = $message['text'];
        $parts = (int) $message['parts'];
        $stmt->execute();
        $ids[] = (int) $conn->insert_id;
    }
    $stmt->close();
    return $ids;
}

/** Credits that were charged for these messages: the parts saved on each row, not a guess from one text. */
function sms_charged_parts($conn, $ids)
{
    $total = 0;
    foreach (array_chunk(array_map('intval', $ids), 200) as $chunk) {
        $marks = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $conn->prepare('SELECT COALESCE(SUM(parts), 0) AS n FROM sms_messages WHERE id IN (' . $marks . ')');
        $stmt->bind_param(str_repeat('i', count($chunk)), ...$chunk);
        $stmt->execute();
        $row = db_fetch_assoc($stmt);
        $stmt->close();
        $total += $row ? (int) $row['n'] : 0;
    }
    return $total;
}

function sms_mark_messages($conn, $ids, $status, $error)
{
    if (!$ids) {
        return;
    }
    $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : '';
    $id = 0;
    $batched = count($ids) > 1 && sms_db_batch($conn, 'begin');
    try {
        $stmt = $conn->prepare('UPDATE sms_messages SET status = ?, error_text = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
        $stmt->bind_param('sssi', $status, $error, $sentAt, $id);
        foreach ($ids as $messageId) {
            $id = (int) $messageId;
            $stmt->execute();
        }
        $stmt->close();
    } catch (Throwable $exception) {
        if ($batched) {
            sms_db_batch($conn, 'rollback');
        }
        throw $exception;
    }
    if ($batched) {
        sms_db_batch($conn, 'commit');
    }
}

function sms_campaign_touch($conn, $campaignId, $at = '')
{
    $at = $at !== '' ? $at : date('Y-m-d H:i:s');
    $campaignId = (int) $campaignId;
    try {
        $stmt = $conn->prepare('UPDATE sms_campaigns SET updated_at = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $at, $campaignId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $exception) {
        error_log('An SMS send could not record its progress time.');
    }
}

function sms_claim_sending($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    $now = date('Y-m-d H:i:s');
    $staleBefore = date('Y-m-d H:i:s', time() - 90);
    $sending = 'sending';
    try {
        $stmt = $conn->prepare('UPDATE sms_campaigns SET updated_at = ? WHERE id = ? AND status = ? AND (updated_at IS NULL OR updated_at <= ?)');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('siss', $now, $campaignId, $sending, $staleBefore);
        $stmt->execute();
        $claimed = (int) $conn->affected_rows > 0;
        $stmt->close();
        return $claimed;
    } catch (Throwable $exception) {
        return false;
    }
}

function sms_resume_sending($conn, $budgetSeconds)
{
    $budgetSeconds = max(5, (int) $budgetSeconds);
    $started = time();
    $staleBefore = date('Y-m-d H:i:s', time() - 90);
    try {
        $stmt = $conn->prepare('SELECT DISTINCT c.id FROM sms_campaigns c JOIN sms_messages m ON m.campaign_id = c.id WHERE c.channel = ? AND c.status = ? AND m.status = ? AND (c.updated_at IS NULL OR c.updated_at <= ?) ORDER BY c.id ASC LIMIT 5');
        if (!$stmt) {
            return;
        }
        $channel = 'sms';
        $sending = 'sending';
        $queued = 'queued';
        $stmt->bind_param('ssss', $channel, $sending, $queued, $staleBefore);
        $stmt->execute();
        $rows = db_fetch_all($stmt);
        $stmt->close();
    } catch (Throwable $exception) {
        error_log('Background SMS sends could not be listed.');
        return;
    }
    foreach ($rows as $row) {
        $left = $budgetSeconds - (time() - $started);
        if ($left < 5) {
            break;
        }
        if (sms_claim_sending($conn, (int) $row['id'])) {
            sms_deliver_campaign($conn, (int) $row['id'], $left);
        }
    }
}

function sms_start_background($conn, $campaignId)
{
    $campaignId = (int) $campaignId;
    if ($campaignId < 1 || (!function_exists('fastcgi_finish_request') && !function_exists('litespeed_finish_request'))) {
        return;
    }
    register_shutdown_function(function () use ($conn, $campaignId) {
        ignore_user_abort(true);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            litespeed_finish_request();
        }
        if (sms_claim_sending($conn, $campaignId)) {
            sms_deliver_campaign($conn, $campaignId, 240);
        }
    });
}

function sms_prohibited_notice($text)
{
    $text = str_replace(array("\r", "\n", "\t"), ' ', (string) $text);
    $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
    if (!is_string($text)) {
        return '';
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    if (!is_string($text) || trim($text) === '') {
        return '';
    }
    $latin = strtolower($text);
    $latin = strtr($latin, array('@' => 'a', '$' => 's', '0' => 'o', '1' => 'i'));
    $patterns = array(
        '/(?<!do not )(?<!don\'t )\b(send|share|give|forward|reply with|enter|submit|whatsapp|viber)\b.{0,40}\b(otp|pin|password|passwd|cvv|mpin)\b/',
        '/\b(otp|pin|password|cvv|mpin)\b.{0,40}\b(pathau|pathaunu|to this number|to me)\b/',
        '/(ओटीपी|पिन कोड|पासवर्ड).{0,24}(?<!न)(पठाउनुहोस्|पठाउनु|दिनुहोस्|लेख्नुहोस्)/u',
        '/(?<!न)(पठाउनुहोस्|पठाउनुहोला|दिनुहोस्).{0,20}(ओटीपी|पासवर्ड|पिन)/u',
        '/\b(you have won|you\'ve won|you won|lucky winner|claim your prize|lottery winner|prize money)\b/',
        '/(जितेर हात|पुरस्कार पाउनुभयो|ल्याटरी जित)/u',
        '/\b(i will kill|bomb threat|pay or else)\b/',
        '/(मार्दिन्छु|मारिदिन्छु|बम राखेको छ)/u',
        '/\b(call girls?|escort service|child porn|underage sex|sex for money)\b/',
        '/\b(kill all|wipe out all)\b.{0,30}\b(muslims?|hindus?|christians?|dalits?|madhesis?)\b/',
        '/\b(cocaine|heroin|mdma)\b.{0,24}\b(for sale|buy now|price)\b/',
        '/(गाँजा|चरस).{0,16}(बेच्छ|बेच्ने|किन्नुहोस्)/u',
        '/\b(online casino|satta matka|cricket betting id)\b/',
        '/\b(account (is|has been) (suspended|blocked|locked))\b.{0,50}\b(click|http|verify now|link)\b/'
    );
    foreach ($patterns as $pattern) {
        $subject = substr($pattern, -2) === '/u' ? $text : $latin;
        if (preg_match($pattern, $subject)) {
            return 'This text cannot be sent. Nepal law does not allow fraud, a threat, sexual content, hate, or a request for a password, PIN, or OTP.';
        }
    }
    return '';
}

function sms_deliver_campaign($conn, $campaignId, $budgetSeconds = 0)
{
    $budgetSeconds = (int) $budgetSeconds;
    $startedAt = time();
    if (function_exists('set_time_limit')) {
        @set_time_limit($budgetSeconds > 0 ? $budgetSeconds + 60 : 180);
    }
    $campaignId = (int) $campaignId;
    $stmt = $conn->prepare('SELECT * FROM sms_campaigns WHERE id = ? AND channel = ?');
    $channel = 'sms';
    $stmt->bind_param('is', $campaignId, $channel);
    $stmt->execute();
    $campaign = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$campaign) {
        return sms_result(false, 'That SMS was not found.');
    }
    $clientId = (int) $campaign['client_id'];
    $text = (string) $campaign['message_content'];
    $blocked = sms_prohibited_notice($text);
    if ($blocked !== '') {
        $queued = $conn->prepare('SELECT id FROM sms_messages WHERE campaign_id = ? AND status IN (\'queued\', \'sending\')');
        $queued->bind_param('i', $campaignId);
        $queued->execute();
        $queuedRows = db_fetch_all($queued);
        $queued->close();
        $refundIds = array();
        foreach ($queuedRows as $queuedRow) {
            $refundIds[] = (int) $queuedRow['id'];
        }
        if ($refundIds) {
            billing_tx($conn, 'begin');
            billing_add_units($conn, $clientId, 'sms', sms_charged_parts($conn, $refundIds));
            sms_mark_messages($conn, $refundIds, 'failed', 'prohibited');
            billing_tx($conn, 'commit');
        }
        $failed = 'failed';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $failed, $campaignId);
        $stmt->execute();
        $stmt->close();
        $balance = billing_unit_balances($conn, $clientId);
        return sms_result(false, $blocked, array(
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $sender = (string) $campaign['sender_id'];
    $parts = sms_message_parts($text);
    $contacts = sms_contacts_from_stored((string) $campaign['recipients_list']);
    $rendered = sms_render_messages($text, $contacts);
    if (!$rendered['ok']) {
        $failed = 'failed';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $failed, $campaignId);
        $stmt->execute();
        $stmt->close();
        return sms_result(false, $rendered['error']);
    }
    $existing = $conn->prepare('SELECT id, recipient, status, message_text, parts FROM sms_messages WHERE campaign_id = ?');
    $existing->bind_param('i', $campaignId);
    $existing->execute();
    $rows = db_fetch_all($existing);
    $existing->close();
    $queue = array();
    if ($rows) {
        // A message left "sending" was being handed to the SMS line when an earlier run stopped.
        // We cannot know whether the line took it, so it is not sent again (no double SMS) and the
        // credits are returned (no charge for a message we cannot confirm).
        $unsure = array();
        foreach ($rows as $index => $row) {
            if ((string) $row['status'] === 'sending') {
                $unsure[] = (int) $row['id'];
                $rows[$index]['status'] = 'failed';
            }
        }
        if ($unsure) {
            billing_tx($conn, 'begin');
            billing_add_units($conn, $clientId, 'sms', sms_charged_parts($conn, $unsure));
            sms_mark_messages($conn, $unsure, 'failed', 'unconfirmed');
            billing_tx($conn, 'commit');
        }
        foreach ($rows as $row) {
            if ((string) $row['status'] === 'queued') {
                $queue[] = array(
                    'id' => (int) $row['id'],
                    'number' => (string) $row['recipient'],
                    'text' => (string) $row['message_text'],
                    'parts' => (int) $row['parts']
                );
            }
        }
    } else {
        $credits = (int) $rendered['credits'];
        if (!sms_take_credits($conn, $clientId, $credits)) {
            $failed = 'failed';
            $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $failed, $campaignId);
            $stmt->execute();
            $stmt->close();
            return sms_result(false, 'There are not enough SMS credits for this message.');
        }
        $ids = sms_insert_rendered($conn, $clientId, $campaignId, 0, 'dashboard', $sender, $rendered['messages']);
        foreach ($rendered['messages'] as $index => $message) {
            $queue[] = array(
                'id' => (int) $ids[$index],
                'number' => $message['number'],
                'text' => $message['text'],
                'parts' => (int) $message['parts']
            );
        }
    }
    $numbers = array();
    foreach ($queue as $item) {
        $numbers[] = $item['number'];
    }
    if (!$numbers) {
        $sentCount = 0;
        foreach ($rows as $row) {
            if ((string) $row['status'] === 'sent') {
                $sentCount++;
            }
        }
        $final = $sentCount > 0 ? 'sent' : 'failed';
        $now = $final === 'sent' ? date('Y-m-d H:i:s') : '';
        $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
        $stmt->bind_param('ssi', $final, $now, $campaignId);
        $stmt->execute();
        $stmt->close();
        $balance = billing_unit_balances($conn, $clientId);
        if ($final === 'failed') {
            return sms_result(false, 'The message could not be sent. Credits for those numbers were returned.', array(
                'balance' => (int) $balance['sms'],
                'campaign_id' => $campaignId
            ));
        }
        return sms_result(true, '', array(
            'message' => 'Sent.',
            'count' => $sentCount,
            'credits' => 0,
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $failedNumbers = 0;
    $creditsSent = 0;
    $total = count($queue);
    $groups = array();
    foreach ($queue as $item) {
        $key = $item['text'];
        if (!isset($groups[$key])) {
            $groups[$key] = array('parts' => (int) $item['parts'], 'numbers' => array(), 'ids' => array());
        }
        $groups[$key]['numbers'][] = $item['number'];
        $groups[$key]['ids'][] = $item['id'];
    }
    $outOfTime = false;
    $handled = 0;
    foreach ($groups as $groupText => $group) {
        $offset = 0;
        $groupTotal = count($group['numbers']);
        while ($offset < $groupTotal) {
            if ($budgetSeconds > 0 && (time() - $startedAt) >= $budgetSeconds) {
                $outOfTime = true;
                break 2;
            }
            sms_campaign_touch($conn, $campaignId);
            $handled += min(100, $groupTotal - $offset);
            $chunkNumbers = array_slice($group['numbers'], $offset, 100);
            $chunkIds = array_slice($group['ids'], $offset, 100);
            $offset += 100;
            $sentIds = array();
            $failIds = array();
            sms_mark_messages($conn, $chunkIds, 'sending', '');
            try {
                $result = sms_vendor_send($conn, $chunkNumbers, $groupText, $sender);
            } catch (Throwable $exception) {
                error_log('SMS line error: ' . $exception->getMessage());
                $result = array('code' => 'line-error', 'rejected' => array());
            }
            billing_tx($conn, 'begin');
            try {
                if ($result['code'] !== '') {
                    sms_mark_messages($conn, $chunkIds, 'failed', $result['code']);
                    billing_add_units($conn, $clientId, 'sms', (int) $group['parts'] * count($chunkNumbers));
                    $failedNumbers += count($chunkNumbers);
                } else {
                    $rejected = array();
                    foreach ($result['rejected'] as $rejectedNumber) {
                        $rejected[(string) $rejectedNumber] = true;
                    }
                    foreach ($chunkNumbers as $index => $number) {
                        if (isset($rejected[(string) $number])) {
                            $failIds[] = $chunkIds[$index];
                        } else {
                            $sentIds[] = $chunkIds[$index];
                        }
                    }
                    sms_mark_messages($conn, $sentIds, 'sent', '');
                    if ($failIds) {
                        sms_mark_messages($conn, $failIds, 'failed', 'not-accepted');
                        billing_add_units($conn, $clientId, 'sms', (int) $group['parts'] * count($failIds));
                        $failedNumbers += count($failIds);
                    }
                    $creditsSent += (int) $group['parts'] * count($sentIds);
                }
            } catch (Throwable $exception) {
                // Could not record the result: leave the rows "sending" so the next run returns the
                // credits instead of guessing. Nothing is charged twice and nothing is sent twice.
                billing_tx($conn, 'rollback');
                error_log('SMS result could not be saved: ' . $exception->getMessage());
                throw $exception;
            }
            billing_tx($conn, 'commit');
        }
    }
    if ($outOfTime) {
        sms_campaign_touch($conn, $campaignId, date('Y-m-d H:i:s', time() - 120));
        $balance = billing_unit_balances($conn, $clientId);
        return sms_result(true, '', array(
            'message' => number_format($total - $handled) . ' SMS are still being sent in the background.',
            'count' => $handled - $failedNumbers,
            'failed' => $failedNumbers,
            'pending' => $total - $handled,
            'credits' => $creditsSent,
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $sentEarlier = 0;
    foreach ($rows as $row) {
        if ((string) $row['status'] === 'sent') {
            $sentEarlier++;
        }
    }
    $status = $failedNumbers === $total && $sentEarlier === 0 ? 'failed' : 'sent';
    $now = $status === 'failed' ? '' : date('Y-m-d H:i:s');
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, sent_at = NULLIF(?, \'\') WHERE id = ?');
    $stmt->bind_param('ssi', $status, $now, $campaignId);
    $stmt->execute();
    $stmt->close();
    $balance = billing_unit_balances($conn, $clientId);
    if ($failedNumbers === $total) {
        return sms_result(false, 'The message could not be sent. Credits for those numbers were returned.', array(
            'balance' => (int) $balance['sms'],
            'campaign_id' => $campaignId,
            'count' => $total
        ));
    }
    $message = $failedNumbers === 0
        ? number_format($total) . ' SMS sent.'
        : 'Some numbers could not be sent. Credits for those numbers were returned.';
    return sms_result(true, '', array(
        'message' => $message,
        'count' => $total - $failedNumbers,
        'failed' => $failedNumbers,
        'credits' => $creditsSent,
        'balance' => (int) $balance['sms'],
        'campaign_id' => $campaignId
    ));
}

function sms_send($conn, $clientId, $job)
{
    $clientId = (int) $clientId;
    $source = (isset($job['source']) && $job['source'] === 'api') ? 'api' : 'dashboard';
    $gate = sms_client_gate($conn, $clientId, false);
    if ($gate !== '') {
        return sms_result(false, $gate);
    }
    $text = billing_plain_block(isset($job['text']) ? $job['text'] : '', 1000);
    if ($text === '') {
        return sms_result(false, 'Write the message.');
    }
    $blocked = sms_prohibited_notice($text);
    if ($blocked !== '') {
        return sms_result(false, $blocked);
    }
    $parsed = sms_collect_contacts(isset($job['numbers']) ? $job['numbers'] : '', $source === 'api' ? 500 : 0);
    if (!$parsed['ok']) {
        return sms_result(false, $parsed['error']);
    }
    $rendered = sms_render_messages($text, $parsed['contacts']);
    if (!$rendered['ok']) {
        return sms_result(false, $rendered['error']);
    }
    $sender = sms_resolve_sender($conn, $clientId, isset($job['sender']) ? $job['sender'] : '');
    if ($sender['error'] !== '') {
        return sms_result(false, $sender['error']);
    }
    $audience = isset($job['audience']) ? (string) $job['audience'] : '';
    $purpose = isset($job['purpose']) ? (string) $job['purpose'] : '';
    if ($source === 'dashboard') {
        $audiences = billing_audiences();
        $purposes = billing_purposes();
        if (!isset($audiences[$audience]) || !isset($purposes[$purpose])) {
            return sms_result(false, 'Choose who it is for and why it is being sent.');
        }
    }
    $name = billing_plain_line(isset($job['name']) ? $job['name'] : '', 120);
    if ($name === '') {
        $name = 'SMS ' . date('Y-m-d H:i');
    }
    $schedule = sms_parse_schedule(isset($job['scheduled_at']) ? $job['scheduled_at'] : '');
    if (!$schedule['ok']) {
        return sms_result(false, $schedule['error'] !== '' ? $schedule['error'] : 'That send time is not valid.');
    }
    $when = $schedule['stored'];
    $future = $schedule['future'];
    $numbers = array();
    foreach ($rendered['messages'] as $renderedMessage) {
        $numbers[] = $renderedMessage['number'];
    }
    $parts = sms_message_parts($text);
    $credits = (int) $rendered['credits'];
    $identityBlock = sms_unverified_block($conn, $clientId, $credits);
    if ($identityBlock !== '') {
        return sms_result(false, $identityBlock);
    }
    $list = sms_store_contacts($parsed['contacts']);
    if ($future) {
        // Credits and the scheduled messages are saved together: both happen or neither does.
        billing_tx($conn, 'begin');
        try {
            if (!sms_take_credits($conn, $clientId, $credits)) {
                billing_tx($conn, 'rollback');
                return sms_result(false, 'There are not enough SMS credits for this message.');
            }
            $campaignId = sms_insert_campaign($conn, $clientId, $name, $text, $sender['sender'], count($numbers), 'scheduled', $when, $audience, $purpose, $list);
            if ($campaignId < 1) {
                billing_tx($conn, 'rollback');
                return sms_result(false, 'That SMS could not be scheduled. Nothing was charged.');
            }
            sms_insert_rendered($conn, $clientId, $campaignId, 0, $source, $sender['sender'], $rendered['messages']);
        } catch (Throwable $exception) {
            billing_tx($conn, 'rollback');
            error_log('SMS schedule failed: ' . $exception->getMessage());
            return sms_result(false, 'That SMS could not be scheduled. Nothing was charged.');
        }
        billing_tx($conn, 'commit');
        $balances = billing_unit_balances($conn, $clientId);
        return sms_result(true, '', array(
            'message' => 'Scheduled for ' . $schedule['label'] . ' Nepal time. ' . number_format($credits) . ' credits are held until it sends. Cancel returns them.',
            'count' => count($numbers),
            'credits' => $credits,
            'balance' => (int) $balances['sms'],
            'campaign_id' => $campaignId
        ));
    }
    $balances = billing_unit_balances($conn, $clientId);
    if ((int) $balances['sms'] < $credits) {
        return sms_result(false, 'There are not enough SMS credits for this message.');
    }
    $tokenId = (int) (isset($job['token_id']) ? $job['token_id'] : 0);
    // The send record, its messages and the credits are saved together. If any step fails, none of
    // them stay: no credits are taken for a send that was never recorded, and no message can be
    // sent for credits that were never taken.
    billing_tx($conn, 'begin');
    try {
        if (!sms_take_credits($conn, $clientId, $credits)) {
            billing_tx($conn, 'rollback');
            return sms_result(false, 'There are not enough SMS credits for this message.');
        }
        $campaignId = sms_insert_campaign($conn, $clientId, $name, $text, $sender['sender'], count($numbers), 'sending', '', $audience, $purpose, $list);
        if ($campaignId < 1) {
            billing_tx($conn, 'rollback');
            return sms_result(false, 'That SMS could not be started. Nothing was charged.');
        }
        sms_insert_rendered($conn, $clientId, $campaignId, $tokenId, $source, $sender['sender'], $rendered['messages']);
    } catch (Throwable $exception) {
        billing_tx($conn, 'rollback');
        error_log('SMS start failed: ' . $exception->getMessage());
        return sms_result(false, 'That SMS could not be started. Nothing was charged.');
    }
    billing_tx($conn, 'commit');
    if (count($numbers) > sms_instant_limit()) {
        sms_campaign_touch($conn, $campaignId, '2000-01-01 00:00:00');
        $balances = billing_unit_balances($conn, $clientId);
        return sms_result(true, '', array(
            'message' => number_format(count($numbers)) . ' SMS are being sent in the background. Follow progress in SMS logs.',
            'count' => count($numbers),
            'credits' => $credits,
            'balance' => (int) $balances['sms'],
            'campaign_id' => $campaignId,
            'background' => true
        ));
    }
    return sms_deliver_campaign($conn, $campaignId);
}

function sms_cancel_scheduled($conn, $clientId, $campaignId)
{
    $clientId = (int) $clientId;
    $campaignId = (int) $campaignId;
    $draft = 'draft';
    $scheduled = 'scheduled';
    $channel = 'sms';
    // Status change, message failures and credit return succeed or fail together. A crash
    // halfway would otherwise leave a draft with held messages and no refund.
    billing_tx($conn, 'begin');
    $stmt = $conn->prepare('UPDATE sms_campaigns SET status = ?, scheduled_at = NULL WHERE id = ? AND client_id = ? AND status = ? AND channel = ?');
    $stmt->bind_param('siiss', $draft, $campaignId, $clientId, $scheduled, $channel);
    $stmt->execute();
    $changed = (int) $conn->affected_rows > 0;
    $stmt->close();
    if (!$changed) {
        billing_tx($conn, 'rollback');
        return '';
    }
    $queued = 'queued';
    $rows = $conn->prepare('SELECT id, parts FROM sms_messages WHERE campaign_id = ? AND client_id = ? AND status = ?');
    $rows->bind_param('iis', $campaignId, $clientId, $queued);
    $rows->execute();
    $held = db_fetch_all($rows);
    $rows->close();
    if (!$held) {
        billing_tx($conn, 'commit');
        return 'released';
    }
    $ids = array();
    $credits = 0;
    foreach ($held as $heldRow) {
        $ids[] = (int) $heldRow['id'];
        $credits += (int) $heldRow['parts'];
    }
    sms_mark_messages($conn, $ids, 'failed', 'cancelled');
    if ($credits > 0) {
        billing_add_units($conn, $clientId, 'sms', $credits);
    }
    billing_tx($conn, 'commit');
    return 'refunded';
}

function sms_run_queue($conn, $limit, $budgetSeconds = 40)
{
    $budgetSeconds = max(5, (int) $budgetSeconds);
    $queueStarted = time();
    $limit = (int) $limit;
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 20) {
        $limit = 20;
    }
    $now = date('Y-m-d H:i:s');
    $channel = 'sms';
    $scheduled = 'scheduled';
    $stmt = $conn->prepare('SELECT id FROM sms_campaigns WHERE channel = ? AND status = ? AND scheduled_at IS NOT NULL AND scheduled_at <= ? ORDER BY scheduled_at ASC LIMIT ' . $limit);
    $stmt->bind_param('sss', $channel, $scheduled, $now);
    $stmt->execute();
    $due = db_fetch_all($stmt);
    $stmt->close();
    $sending = 'sending';
    $hasUpdated = false;
    try {
        $campaignColumns = array_flip(billing_table_columns($conn, 'sms_campaigns'));
        $hasUpdated = isset($campaignColumns['updated_at']);
    } catch (Throwable $exception) {
        $hasUpdated = false;
    }
    $claimSql = $hasUpdated
        ? 'UPDATE sms_campaigns SET status = ?, updated_at = ? WHERE id = ? AND status = ?'
        : 'UPDATE sms_campaigns SET status = ? WHERE id = ? AND status = ?';
    $claim = $conn->prepare($claimSql);
    if (!$claim) {
        return;
    }
    foreach ($due as $row) {
        $left = $budgetSeconds - (time() - $queueStarted);
        if ($left < 3) {
            break;
        }
        $id = (int) $row['id'];
        if ($hasUpdated) {
            $claimedAt = date('Y-m-d H:i:s');
            $claim->bind_param('ssis', $sending, $claimedAt, $id, $scheduled);
        } else {
            $claim->bind_param('sis', $sending, $id, $scheduled);
        }
        $claim->execute();
        if ((int) $conn->affected_rows > 0) {
            sms_deliver_campaign($conn, $id, $hasUpdated ? $left : 0);
        }
    }
    $claim->close();
    if (!$hasUpdated) {
        return;
    }
    try {
        $cutoff = date('Y-m-d H:i:s', time() - 600);
        $stuck = $conn->prepare('SELECT c.id, c.scheduled_at FROM sms_campaigns c WHERE c.channel = ? AND c.status = ? AND c.updated_at <= ? AND NOT EXISTS (SELECT 1 FROM sms_messages m WHERE m.campaign_id = c.id) LIMIT 20');
        if ($stuck) {
            $stuck->bind_param('sss', $channel, $sending, $cutoff);
            $stuck->execute();
            $abandoned = db_fetch_all($stuck);
            $stuck->close();
            $back = 'scheduled';
            $failed = 'failed';
            $restore = $conn->prepare('UPDATE sms_campaigns SET status = ? WHERE id = ? AND status = ?');
            if ($restore) {
                foreach ($abandoned as $abandonedRow) {
                    $abandonedId = (int) $abandonedRow['id'];
                    $next = trim((string) $abandonedRow['scheduled_at']) !== '' ? $back : $failed;
                    $restore->bind_param('sis', $next, $abandonedId, $sending);
                    $restore->execute();
                }
                $restore->close();
            }
        }
        $left = $budgetSeconds - (time() - $queueStarted);
        if ($left >= 5) {
            sms_resume_sending($conn, $left);
        }
    } catch (Throwable $exception) {
        error_log('A stuck SMS could not be resumed.');
    }
}

/**
 * Safety net for hosts without cron: when a client opens a page, finish any send that stopped part
 * way. At most once a minute, a few seconds at a time, and never an error for the visitor.
 */
function sms_lazy_resume($conn)
{
    try {
        $last = (int) billing_setting($conn, 'sms_lazy_run_at');
        if (time() - $last < 60) {
            return;
        }
        billing_set_setting($conn, 'sms_lazy_run_at', (string) time());
        sms_resume_sending($conn, 12);
    } catch (Throwable $exception) {
        error_log('Lazy SMS resume failed: ' . $exception->getMessage());
    }
}
