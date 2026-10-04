<?php
/**
 * SMS: delivery report numbers (client and admin). Everything is counted from sms_messages in one
 * period, optionally for one client or one send. Works on MySQL and SQLite.
 *
 * Words used on screen (same on every page):
 *   Delivered          the phone network confirmed it reached the phone
 *   Awaiting           accepted by the SMS line, no confirmation yet
 *   Not delivered      the network said it could not deliver
 *   Rejected           the SMS line refused it (credits are returned)
 */

function sms_carrier_for($number)
{
    $prefix = substr(preg_replace('/\D/', '', (string) $number), -10, 3);
    $map = array(
        '984' => 'NTC', '985' => 'NTC', '986' => 'NTC', '976' => 'NTC', '974' => 'NTC', '975' => 'NTC',
        '980' => 'Ncell', '981' => 'Ncell', '982' => 'Ncell', '970' => 'Ncell', '971' => 'Ncell',
        '961' => 'Smart Cell', '962' => 'Smart Cell', '988' => 'Smart Cell'
    );
    return isset($map[$prefix]) ? $map[$prefix] : 'Other';
}

function sms_failure_explained($code)
{
    $known = array(
        'not-accepted' => array('Number not accepted', 'The SMS line did not accept these numbers. Check that each is a working Nepal mobile, then send again.'),
        'sender-rejected' => array('Sender name not approved', 'Send with a sender name that is approved for your account, or request a new one.'),
        'line-off' => array('SMS line unavailable', 'The SMS line was switched off for a moment. Credits were returned. Send again shortly.'),
        'line-empty' => array('SMS line gave no answer', 'No answer came back from the SMS line. Credits were returned. Send again shortly.'),
        'line-rejected' => array('SMS line refused the send', 'The SMS line refused this send. Credits were returned. Contact support if it repeats.'),
        'credits' => array('Not enough credits', 'Credits ran out while sending. Add credits and send the rest.'),
        'prohibited' => array('Message not allowed', 'The message was blocked because it breaks the use rules. Credits were returned.'),
        'line-error' => array('SMS line error', 'The SMS line could not be reached. Credits were returned. Send again shortly.'),
        'unconfirmed' => array('Could not confirm it was sent', 'A send was interrupted, so we could not confirm this message left. Credits were returned and it was not sent again. If the phone did not get it, send it again.'),
        'cancelled' => array('Cancelled before sending', 'This send was cancelled and the credits were returned.')
    );
    $code = (string) $code;
    if (isset($known[$code])) {
        return array('title' => $known[$code][0], 'help' => $known[$code][1]);
    }
    return array('title' => $code !== '' ? ucfirst(substr($code, 0, 40)) : 'Reason not recorded', 'help' => 'Open the message in SMS logs for details.');
}

function sms_percent($part, $whole)
{
    return $whole > 0 ? (int) round($part * 100 / $whole) : null;
}

function sms_report_range($from, $to)
{
    $today = date('Y-m-d');
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $to) ? (string) $to : $today;
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $from) ? (string) $from : date('Y-m-d', strtotime($to . ' -6 days'));
    if ($from > $to) {
        $swap = $from;
        $from = $to;
        $to = $swap;
    }
    if (strtotime($to) - strtotime($from) > 91 * 86400) {
        $from = date('Y-m-d', strtotime($to . ' -91 days'));
    }
    return array($from, $to);
}

/** Runs one aggregate query with the shared filters. $select and $group are fixed strings from this file. */
function sms_report_query($conn, $select, $tail, $filters)
{
    $sql = 'SELECT ' . $select . ' FROM sms_messages m WHERE COALESCE(m.sent_at, m.created_at) >= ? AND COALESCE(m.sent_at, m.created_at) < ?';
    $types = 'ss';
    $values = array($filters['start'], $filters['end']);
    if ($filters['client'] > 0) {
        $sql .= ' AND m.client_id = ?';
        $types .= 'i';
        $values[] = $filters['client'];
    }
    if ($filters['send'] > 0) {
        $sql .= ' AND m.campaign_id = ?';
        $types .= 'i';
        $values[] = $filters['send'];
    }
    $stmt = $conn->prepare($sql . ' ' . $tail);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows ?: array();
}

function sms_report($conn, $clientId, $from, $to, $sendId = 0)
{
    sms_delivery_columns($conn);
    list($from, $to) = sms_report_range($from, $to);
    $filters = array('client' => (int) $clientId, 'send' => (int) $sendId, 'start' => $from . ' 00:00:00', 'end' => date('Y-m-d 00:00:00', strtotime($to . ' +1 day')));
    $count = function ($condition) {
        return 'SUM(CASE WHEN ' . $condition . ' THEN 1 ELSE 0 END)';
    };
    $flags = $count("m.status = 'sent' AND m.delivery = 'delivered'") . ' AS delivered, '
        . $count("m.status = 'sent' AND m.delivery = 'failed'") . ' AS undelivered, '
        . $count("m.status = 'sent' AND (m.delivery IS NULL OR m.delivery = '')") . ' AS awaiting, '
        . $count("m.status = 'failed'") . ' AS rejected, '
        . $count("m.status = 'queued'") . ' AS queued';

    $row = sms_report_query($conn, 'COUNT(*) AS total, ' . $flags . ', COALESCE(SUM(m.parts), 0) AS parts', '', $filters);
    $t = $row ? $row[0] : array();
    $total = (int) ($t['total'] ?? 0);
    $funnel = array(
        'total' => $total,
        'accepted' => (int) ($t['delivered'] ?? 0) + (int) ($t['undelivered'] ?? 0) + (int) ($t['awaiting'] ?? 0),
        'delivered' => (int) ($t['delivered'] ?? 0),
        'undelivered' => (int) ($t['undelivered'] ?? 0),
        'awaiting' => (int) ($t['awaiting'] ?? 0),
        'rejected' => (int) ($t['rejected'] ?? 0),
        'queued' => (int) ($t['queued'] ?? 0),
        'parts' => (int) ($t['parts'] ?? 0)
    );
    $reported = $funnel['delivered'] + $funnel['undelivered'];

    // Daily trend
    $series = array();
    for ($ts = strtotime($from); $ts <= strtotime($to); $ts += 86400) {
        $series[date('Y-m-d', $ts)] = array('day' => date('Y-m-d', $ts), 'delivered' => 0, 'awaiting' => 0, 'undelivered' => 0, 'rejected' => 0, 'queued' => 0);
    }
    foreach (sms_report_query($conn, 'DATE(COALESCE(m.sent_at, m.created_at)) AS d, ' . $flags, 'GROUP BY d', $filters) as $r) {
        if (isset($series[$r['d']])) {
            foreach (array('delivered', 'awaiting', 'undelivered', 'rejected', 'queued') as $k) {
                $series[$r['d']][$k] = (int) $r[$k];
            }
        }
    }

    // Carrier (from the number prefix; ported numbers can differ)
    $carriers = array();
    foreach (sms_report_query($conn, 'SUBSTR(m.recipient, -10, 3) AS p, COUNT(*) AS total, ' . $flags, 'GROUP BY p', $filters) as $r) {
        $name = sms_carrier_for($r['p'] . '0000000');
        if (!isset($carriers[$name])) {
            $carriers[$name] = array('name' => $name, 'total' => 0, 'delivered' => 0, 'undelivered' => 0, 'awaiting' => 0, 'rejected' => 0);
        }
        foreach (array('total', 'delivered', 'undelivered', 'awaiting', 'rejected') as $k) {
            $carriers[$name][$k] += (int) $r[$k];
        }
    }
    foreach ($carriers as &$c) {
        $c['rate'] = sms_percent($c['delivered'], $c['delivered'] + $c['undelivered']);
    }
    unset($c);
    uasort($carriers, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    // Why messages did not go
    $reasons = array();
    foreach (sms_report_query($conn, "m.error_text AS code, COUNT(*) AS n", "AND m.status = 'failed' GROUP BY m.error_text ORDER BY n DESC LIMIT 8", $filters) as $r) {
        $info = sms_failure_explained($r['code']);
        $reasons[] = array('code' => (string) $r['code'], 'title' => $info['title'], 'help' => $info['help'], 'count' => (int) $r['n']);
    }

    // How fast: seconds from "sent" to "delivered"
    $seconds = array();
    foreach (sms_report_query($conn, 'm.sent_at AS s, m.delivery_at AS d', "AND m.delivery = 'delivered' AND m.sent_at IS NOT NULL AND m.delivery_at IS NOT NULL ORDER BY m.id DESC LIMIT 5000", $filters) as $r) {
        $gap = strtotime($r['d']) - strtotime($r['s']);
        if ($gap >= 0 && $gap < 7 * 86400) {
            $seconds[] = $gap;
        }
    }
    sort($seconds);
    $pick = function ($q) use ($seconds) {
        return $seconds ? $seconds[(int) min(count($seconds) - 1, floor(count($seconds) * $q))] : null;
    };

    // Sends (campaigns) in this period
    $sends = array();
    foreach (sms_report_query($conn, 'm.campaign_id AS cid, COUNT(*) AS total, ' . $flags, 'AND m.campaign_id > 0 GROUP BY m.campaign_id ORDER BY m.campaign_id DESC LIMIT 12', $filters) as $r) {
        $name = '';
        $stmt = $conn->prepare('SELECT campaign_name, created_at FROM sms_campaigns WHERE id = ?');
        $cid = (int) $r['cid'];
        $stmt->bind_param('i', $cid);
        $stmt->execute();
        $info = db_fetch_assoc($stmt);
        $stmt->close();
        $sends[] = array('id' => $cid, 'name' => $info ? (string) $info['campaign_name'] : 'Send #' . $cid, 'at' => $info ? (string) $info['created_at'] : '',
            'total' => (int) $r['total'], 'delivered' => (int) $r['delivered'], 'undelivered' => (int) $r['undelivered'], 'awaiting' => (int) $r['awaiting'], 'rejected' => (int) $r['rejected']);
    }

    // Accepted more than a day ago and still no word
    $old = date('Y-m-d H:i:s', strtotime('-1 day'));
    $stale = sms_report_query($conn, 'COUNT(*) AS n', "AND m.status = 'sent' AND (m.delivery IS NULL OR m.delivery = '') AND m.sent_at < '" . $old . "'", $filters);
    $staleCount = $stale ? (int) $stale[0]['n'] : 0;

    $stuckSince = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stuck = sms_report_query($conn, 'COUNT(*) AS n', "AND m.status IN ('queued', 'sending') AND m.created_at < '" . $stuckSince . "' AND m.campaign_id IN (SELECT id FROM sms_campaigns WHERE status = 'sending')", $filters);

    $report = array(
        'stuck' => $stuck ? (int) $stuck[0]['n'] : 0,
        'from' => $from, 'to' => $to, 'funnel' => $funnel, 'series' => array_values($series), 'carriers' => array_values($carriers), 'reasons' => $reasons, 'sends' => $sends,
        'rate' => sms_percent($funnel['delivered'], $reported),
        'confirmed_share' => sms_percent($reported, $funnel['accepted']),
        'rejected_rate' => sms_percent($funnel['rejected'], $funnel['accepted'] + $funnel['rejected']),
        'median' => $pick(0.5), 'p90' => $pick(0.9), 'stale' => $staleCount
    );
    $report['insights'] = sms_report_insights($report);
    return $report;
}

function sms_duration_label($seconds)
{
    if ($seconds === null) {
        return '—';
    }
    if ($seconds < 60) {
        return $seconds . ' sec';
    }
    if ($seconds < 3600) {
        return round($seconds / 60) . ' min';
    }
    return round($seconds / 3600, 1) . ' hr';
}

/** Plain-language findings, most important first. tone: good, info, warn, bad. */
function sms_report_insights($r)
{
    $f = $r['funnel'];
    $out = array();
    if (!empty($r['stuck'])) {
        array_unshift($out, array('tone' => 'warn', 'title' => number_format($r['stuck']) . ' messages are taking too long to send', 'text' => 'They are finished automatically within a few minutes. If a message cannot be sent, its credits are returned, never kept.'));
    }
    if ($f['total'] === 0) {
        return array(array('tone' => 'info', 'title' => 'No messages in this period', 'text' => 'Pick a longer period, or send your first SMS.'));
    }
    if ($f['accepted'] > 0 && ($f['delivered'] + $f['undelivered']) === 0) {
        $out[] = array('tone' => 'info', 'title' => 'Waiting for network confirmation', 'text' => 'The SMS line accepted ' . number_format($f['accepted']) . ' messages. Delivery confirmations have not arrived yet, so they are shown as awaiting rather than guessed.');
    } elseif ($r['rate'] !== null) {
        if ($r['rate'] >= 95) {
            $out[] = array('tone' => 'good', 'title' => 'Delivery is healthy', 'text' => $r['rate'] . '% of confirmed messages reached the phone.');
        } elseif ($r['rate'] >= 85) {
            $out[] = array('tone' => 'info', 'title' => 'Delivery is fair', 'text' => $r['rate'] . '% of confirmed messages reached the phone. Some numbers may be switched off or inactive.');
        } else {
            $out[] = array('tone' => 'bad', 'title' => 'Delivery is low', 'text' => 'Only ' . $r['rate'] . '% of confirmed messages reached the phone. Check the numbers and the carrier split below.');
        }
    }
    $rated = array_values(array_filter($r['carriers'], function ($c) {
        return $c['rate'] !== null && ($c['delivered'] + $c['undelivered']) >= 20;
    }));
    if (count($rated) >= 2) {
        usort($rated, function ($a, $b) {
            return $b['rate'] <=> $a['rate'];
        });
        $gap = $rated[0]['rate'] - $rated[count($rated) - 1]['rate'];
        if ($gap >= 15) {
            $low = $rated[count($rated) - 1];
            $out[] = array('tone' => 'warn', 'title' => $low['name'] . ' delivers ' . $gap . ' points lower', 'text' => $low['name'] . ' is at ' . $low['rate'] . '% against ' . $rated[0]['rate'] . '% for ' . $rated[0]['name'] . '. A carrier-side delay is the usual cause; it often clears by itself.');
        }
    }
    if ($r['rejected_rate'] !== null && $r['rejected_rate'] >= 5 && $r['reasons']) {
        $out[] = array('tone' => 'warn', 'title' => number_format($f['rejected']) . ($f['rejected'] === 1 ? ' message was refused' : ' messages were refused'), 'text' => $r['reasons'][0]['title'] . ': ' . $r['reasons'][0]['help']);
    }
    if ($r['stale'] > 0) {
        $out[] = array('tone' => 'info', 'title' => number_format($r['stale']) . ' still unconfirmed after a day', 'text' => 'Phones that are off or out of coverage can take a while. Messages stay in the network for a limited time, then may expire.');
    }
    if ($r['median'] !== null && $r['median'] > 120) {
        $out[] = array('tone' => 'info', 'title' => 'Delivery is slower than usual', 'text' => 'Half of the messages took longer than ' . sms_duration_label($r['median']) . '. Time-sensitive messages such as OTP should be sent separately.');
    }
    return $out;
}

/** Admin: which clients sent most in the period, with their delivery rate. */
function sms_report_by_client($conn, $from, $to, $limit = 8)
{
    sms_delivery_columns($conn);
    list($from, $to) = sms_report_range($from, $to);
    $start = $from . ' 00:00:00';
    $end = date('Y-m-d 00:00:00', strtotime($to . ' +1 day'));
    $limit = max(1, min(20, (int) $limit));
    $stmt = $conn->prepare("SELECT c.id, c.name, COUNT(*) AS total,"
        . " SUM(CASE WHEN m.delivery = 'delivered' THEN 1 ELSE 0 END) AS delivered,"
        . " SUM(CASE WHEN m.delivery = 'failed' THEN 1 ELSE 0 END) AS undelivered,"
        . " SUM(CASE WHEN m.status = 'failed' THEN 1 ELSE 0 END) AS rejected"
        . ' FROM sms_messages m JOIN client_users c ON c.id = m.client_id WHERE COALESCE(m.sent_at, m.created_at) >= ? AND COALESCE(m.sent_at, m.created_at) < ?'
        . ' GROUP BY c.id, c.name ORDER BY total DESC LIMIT ' . $limit);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $rows = db_fetch_all($stmt) ?: array();
    $stmt->close();
    foreach ($rows as &$row) {
        $row['rate'] = sms_percent((int) $row['delivered'], (int) $row['delivered'] + (int) $row['undelivered']);
    }
    unset($row);
    return $rows;
}
