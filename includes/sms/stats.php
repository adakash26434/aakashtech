<?php
/**
 * SMS: numbers for the dashboards (last N days). One query per view, portable across MySQL and SQLite.
 */

function sms_overview($conn, $clientId = 0, $days = 7)
{
    sms_delivery_columns($conn);
    $days = max(1, min(31, (int) $days));
    $clientId = (int) $clientId;
    $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
    $series = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime('-' . $i . ' days'));
        $series[$day] = array('day' => $day, 'sent' => 0, 'failed' => 0, 'queued' => 0, 'delivered' => 0, 'undelivered' => 0);
    }
    $sql = 'SELECT DATE(COALESCE(sent_at, created_at)) AS d,'
        . " SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,"
        . " SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,"
        . " SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued,"
        . " SUM(CASE WHEN delivery = 'delivered' THEN 1 ELSE 0 END) AS delivered,"
        . " SUM(CASE WHEN delivery = 'failed' THEN 1 ELSE 0 END) AS undelivered"
        . ' FROM sms_messages WHERE COALESCE(sent_at, created_at) >= ?'
        . ($clientId > 0 ? ' AND client_id = ?' : '')
        . ' GROUP BY d';
    $stmt = $conn->prepare($sql);
    if ($clientId > 0) {
        $stmt->bind_param('si', $since, $clientId);
    } else {
        $stmt->bind_param('s', $since);
    }
    $stmt->execute();
    foreach (db_fetch_all($stmt) as $row) {
        if (isset($series[$row['d']])) {
            foreach (array('sent', 'failed', 'queued', 'delivered', 'undelivered') as $key) {
                $series[$row['d']][$key] = (int) $row[$key];
            }
        }
    }
    $stmt->close();
    $totals = array('sent' => 0, 'failed' => 0, 'queued' => 0, 'delivered' => 0, 'undelivered' => 0);
    foreach ($series as $point) {
        foreach ($totals as $key => $unused) {
            $totals[$key] += $point[$key];
        }
    }
    $reports = $totals['delivered'] + $totals['undelivered'];
    $attempts = $totals['sent'] + $totals['failed'];
    return array(
        'days' => $days,
        'series' => array_values($series),
        'totals' => $totals,
        'delivery_rate' => $reports > 0 ? (int) round($totals['delivered'] * 100 / $reports) : null,
        'failure_rate' => $attempts > 0 ? (int) round($totals['failed'] * 100 / $attempts) : null
    );
}

/** Admin: the clients sending the most in the last 30 days. */
function sms_top_senders($conn, $limit = 5)
{
    $since = date('Y-m-d 00:00:00', strtotime('-29 days'));
    $limit = max(1, min(10, (int) $limit));
    $stmt = $conn->prepare("SELECT c.id, c.name, COUNT(*) AS sent FROM sms_messages m JOIN client_users c ON c.id = m.client_id WHERE m.status = 'sent' AND COALESCE(m.sent_at, m.created_at) >= ? GROUP BY c.id, c.name ORDER BY sent DESC LIMIT " . $limit);
    $stmt->bind_param('s', $since);
    $stmt->execute();
    $rows = db_fetch_all($stmt);
    $stmt->close();
    return $rows ?: array();
}
