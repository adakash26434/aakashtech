<?php
/**
 * SMS at a glance: used by the client and admin dashboards so both look the same.
 * Each function prints HTML; all values are escaped.
 */

function sms_overview_chart($series)
{
    $max = 1;
    foreach ($series as $point) {
        $max = max($max, $point['sent'] + $point['failed'] + $point['queued']);
    }
    $count = count($series);
    $width = 100 / max(1, $count);
    $html = '<svg class="sms-chart" viewBox="0 0 100 46" preserveAspectRatio="none" role="img" aria-label="Messages per day">';
    foreach ($series as $i => $point) {
        $x = $i * $width + $width * 0.18;
        $bar = $width * 0.64;
        $y = 40;
        foreach (array('sent' => 'is-sent', 'failed' => 'is-failed', 'queued' => 'is-queued') as $key => $class) {
            $h = $point[$key] > 0 ? max(1.5, $point[$key] / $max * 36) : 0;
            if ($h > 0) {
                $y -= $h;
                $html .= '<rect class="sms-bar ' . $class . '" x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($bar, 2) . '" height="' . round($h, 2) . '" rx="1"><title>'
                    . htmlspecialchars(date('D j M', strtotime($point['day'])) . ': ' . $point[$key] . ' ' . $key, ENT_QUOTES, 'UTF-8') . '</title></rect>';
            }
        }
        if ($point['sent'] + $point['failed'] + $point['queued'] === 0) {
            $html .= '<rect class="sms-bar is-empty" x="' . round($x, 2) . '" y="38.5" width="' . round($bar, 2) . '" height="1.5" rx="0.7"></rect>';
        }
    }
    $html .= '</svg><div class="sms-chart-days" aria-hidden="true">';
    foreach ($series as $point) {
        $html .= '<span>' . htmlspecialchars(substr(date('D', strtotime($point['day'])), 0, 2), ENT_QUOTES, 'UTF-8') . '</span>';
    }
    return $html . '</div>';
}

function sms_overview_stat($label, $value, $note = '', $tone = '')
{
    return '<div class="sms-stat' . ($tone !== '' ? ' is-' . $tone : '') . '"><span class="sms-stat-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</span><strong class="sms-stat-value">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</strong>'
        . ($note !== '' ? '<span class="sms-stat-note">' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</span>' : '') . '</div>';
}

function sms_overview_rates($ov)
{
    $delivery = $ov['delivery_rate'] === null ? '—' : $ov['delivery_rate'] . '%';
    $deliveryNote = $ov['delivery_rate'] === null ? 'Shown when the phone network reports back' : 'of reported messages arrived';
    $failure = $ov['failure_rate'] === null ? '—' : $ov['failure_rate'] . '%';
    return array($delivery, $deliveryNote, $failure);
}

function sms_overview_render_client($ov, $credits, $lowAt = 100)
{
    $credits = (int) $credits;
    $state = $credits <= 0 ? 'empty' : ($credits < $lowAt ? 'low' : 'ok');
    $stateText = $state === 'empty' ? 'No credits left' : ($state === 'low' ? 'Running low' : 'Ready to send');
    list($delivery, $deliveryNote, $failure) = sms_overview_rates($ov);
    $sent = (int) $ov['totals']['sent'];
    echo '<section class="sms-dash" aria-label="SMS overview"><div class="sms-dash-head"><div><h2 class="sms-dash-title">SMS at a glance</h2><p class="sms-dash-sub">Last ' . (int) $ov['days'] . ' days</p></div>'
        . '<div class="sms-dash-actions"><a class="sms-dash-btn is-primary" href="sms-portal.php">Send SMS</a><a class="sms-dash-btn" href="shop.php">Buy credits</a><a class="sms-dash-btn" href="sms-report.php">Delivery report</a></div></div>';
    echo '<div class="sms-dash-grid"><div class="sms-credit is-' . $state . '"><span class="sms-stat-label">Credits left</span><strong class="sms-credit-value">' . number_format($credits) . '</strong><span class="sms-credit-state">' . htmlspecialchars($stateText, ENT_QUOTES, 'UTF-8') . '</span>';
    if ($state !== 'ok') {
        echo '<a class="sms-credit-cta" href="shop.php">Add credits</a>';
    }
    echo '</div><div class="sms-stats">'
        . sms_overview_stat('Sent', number_format($sent), 'accepted by the line')
        . sms_overview_stat('Delivered', $delivery, $deliveryNote, $ov['delivery_rate'] !== null && $ov['delivery_rate'] < 80 ? 'warn' : '')
        . sms_overview_stat('Failed', number_format((int) $ov['totals']['failed']), $failure === '—' ? 'none yet' : $failure . ' of attempts', (int) $ov['totals']['failed'] > 0 ? 'warn' : '')
        . '</div><div class="sms-chart-wrap">' . sms_overview_chart($ov['series']) . '<p class="sms-legend"><span class="is-sent">Sent</span><span class="is-failed">Failed</span><span class="is-queued">Waiting</span></p></div></div></section>';
}

function sms_overview_render_admin($ov, $stock, $held, $top)
{
    list($delivery, $deliveryNote, $failure) = sms_overview_rates($ov);
    $held = (int) $held;
    $hasStock = $stock !== null;
    $stock = (int) $stock;
    $short = $hasStock && $stock < $held;
    $pct = $held > 0 && $hasStock ? min(100, (int) round($stock * 100 / $held)) : 100;
    echo '<section class="sms-dash" aria-label="SMS overview"><div class="sms-dash-head"><div><h2 class="sms-dash-title">SMS health</h2><p class="sms-dash-sub">All clients, last ' . (int) $ov['days'] . ' days</p></div>'
        . '<div class="sms-dash-actions"><a class="sms-dash-btn is-primary" href="sms-line.php">SMS line</a><a class="sms-dash-btn" href="sms-report.php">Delivery report</a><a class="sms-dash-btn" href="sms-line.php?tab=names">Sender names</a></div></div>';
    echo '<div class="sms-dash-grid"><div class="sms-credit is-' . ($short ? 'empty' : 'ok') . '"><span class="sms-stat-label">Bulk stock vs client credits</span>'
        . '<strong class="sms-credit-value">' . ($hasStock ? number_format($stock) : '—') . '</strong>'
        . '<div class="sms-meter" role="img" aria-label="Stock covers ' . $pct . ' percent of client credits"><span style="width:' . $pct . '%"></span></div>'
        . '<span class="sms-credit-state">' . ($hasStock ? ($short ? 'Short by ' . number_format($held - $stock) . ' SMS: buy more from the vendor' : 'Covers all ' . number_format($held) . ' client credits') : 'Press Check on the SMS line page') . '</span></div>'
        . '<div class="sms-stats">'
        . sms_overview_stat('Sent', number_format((int) $ov['totals']['sent']), 'across all clients')
        . sms_overview_stat('Delivered', $delivery, $deliveryNote, $ov['delivery_rate'] !== null && $ov['delivery_rate'] < 80 ? 'warn' : '')
        . sms_overview_stat('Failed', number_format((int) $ov['totals']['failed']), $failure === '—' ? 'none yet' : $failure . ' of attempts', (int) $ov['totals']['failed'] > 0 ? 'warn' : '')
        . '</div><div class="sms-chart-wrap">' . sms_overview_chart($ov['series']) . '<p class="sms-legend"><span class="is-sent">Sent</span><span class="is-failed">Failed</span><span class="is-queued">Waiting</span></p></div></div>';
    if ($top) {
        echo '<div class="sms-top"><h3>Top senders, 30 days</h3><ol>';
        foreach ($top as $row) {
            echo '<li><a href="client.php?id=' . (int) $row['id'] . '">' . htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') . '</a><span>' . number_format((int) $row['sent']) . '</span></li>';
        }
        echo '</ol></div>';
    }
    echo '</section>';
}
