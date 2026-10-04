<?php
/** Delivery report screen shared by the client and admin portals. All output is escaped. */

function sms_rv_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sms_rv_num($value)
{
    return number_format((int) $value);
}

function sms_rv_filters($r, $self, $sends, $extraHidden = array())
{
    $today = date('Y-m-d');
    $chips = array('Today' => array($today, $today), '7 days' => array(date('Y-m-d', strtotime('-6 days')), $today), '30 days' => array(date('Y-m-d', strtotime('-29 days')), $today));
    echo '<form class="smsr-filters" method="get" action="' . sms_rv_e($self) . '"><div class="smsr-chips" role="group" aria-label="Period">';
    foreach ($chips as $label => $range) {
        $active = $r['from'] === $range[0] && $r['to'] === $range[1];
        echo '<a class="smsr-chip' . ($active ? ' is-on' : '') . '" href="' . sms_rv_e($self . '?from=' . $range[0] . '&to=' . $range[1]) . '"' . ($active ? ' aria-current="true"' : '') . '>' . sms_rv_e($label) . '</a>';
    }
    echo '</div><label class="smsr-field"><span>From</span><input type="date" name="from" value="' . sms_rv_e($r['from']) . '" max="' . $today . '"></label>'
        . '<label class="smsr-field"><span>To</span><input type="date" name="to" value="' . sms_rv_e($r['to']) . '" max="' . $today . '"></label>';
    foreach ($extraHidden as $name => $value) {
        echo '<input type="hidden" name="' . sms_rv_e($name) . '" value="' . sms_rv_e($value) . '">';
    }
    if ($sends !== null) {
        echo '<label class="smsr-field"><span>Send</span><select name="send"><option value="0">All sends</option>';
        foreach ($sends as $send) {
            echo '<option value="' . (int) $send['id'] . '"' . ((int) $send['selected'] === 1 ? ' selected' : '') . '>' . sms_rv_e($send['name']) . '</option>';
        }
        echo '</select></label>';
    }
    echo '<button class="smsr-apply" type="submit">Show report</button></form>';
}

function sms_rv_funnel($f)
{
    $total = max(1, $f['total']);
    $parts = array(
        array('delivered', 'Delivered', $f['delivered'], 'The phone network confirmed it reached the phone'),
        array('awaiting', 'Awaiting confirmation', $f['awaiting'], 'Accepted by the SMS line, no confirmation yet'),
        array('undelivered', 'Not delivered', $f['undelivered'], 'The network could not deliver (phone off, out of coverage or number inactive)'),
        array('rejected', 'Refused', $f['rejected'], 'The SMS line refused it; credits were returned'),
        array('queued', 'Waiting to send', $f['queued'], 'Scheduled or still in the queue')
    );
    echo '<div class="smsr-funnel" role="img" aria-label="Where your messages ended up">';
    foreach ($parts as $p) {
        if ($p[2] > 0) {
            echo '<span class="smsr-seg is-' . $p[0] . '" style="flex-grow:' . max(1, round($p[2] * 1000 / $total)) . '" title="' . sms_rv_e($p[1] . ': ' . number_format($p[2])) . '"></span>';
        }
    }
    echo '</div><ul class="smsr-key">';
    foreach ($parts as $p) {
        echo '<li class="is-' . $p[0] . '"><b>' . sms_rv_num($p[2]) . '</b> <span>' . sms_rv_e($p[1]) . '</span><small>' . sms_rv_e($p[3]) . '</small></li>';
    }
    echo '</ul>';
}

function sms_rv_chart($series)
{
    $max = 1;
    foreach ($series as $p) {
        $max = max($max, $p['delivered'] + $p['awaiting'] + $p['undelivered'] + $p['rejected'] + $p['queued']);
    }
    $n = count($series);
    $w = 100 / max(1, $n);
    $out = '<svg class="sms-chart smsr-chart" viewBox="0 0 100 46" preserveAspectRatio="none" role="img" aria-label="Messages per day by result">';
    foreach ($series as $i => $p) {
        $x = $i * $w + $w * 0.15;
        $bw = $w * 0.7;
        $y = 40;
        foreach (array('delivered', 'awaiting', 'undelivered', 'rejected', 'queued') as $k) {
            if ($p[$k] > 0) {
                $h = max(1.2, $p[$k] / $max * 36);
                $y -= $h;
                $out .= '<rect class="smsr-bar is-' . $k . '" x="' . round($x, 2) . '" y="' . round($y, 2) . '" width="' . round($bw, 2) . '" height="' . round($h, 2) . '"><title>' . sms_rv_e(date('D j M', strtotime($p['day'])) . ': ' . $p[$k] . ' ' . $k) . '</title></rect>';
            }
        }
    }
    $out .= '</svg><div class="sms-chart-days" aria-hidden="true">';
    $step = $n > 14 ? (int) ceil($n / 7) : 1;
    foreach ($series as $i => $p) {
        $out .= '<span>' . ($i % $step === 0 ? sms_rv_e(date($n > 10 ? 'j M' : 'D', strtotime($p['day']))) : '') . '</span>';
    }
    return $out . '</div>';
}

function sms_rv_bar($delivered, $awaiting, $bad)
{
    $sum = max(1, $delivered + $awaiting + $bad);
    return '<span class="smsr-mini" aria-hidden="true"><i class="is-delivered" style="width:' . round($delivered * 100 / $sum, 1) . '%"></i><i class="is-awaiting" style="width:' . round($awaiting * 100 / $sum, 1) . '%"></i><i class="is-undelivered" style="width:' . round($bad * 100 / $sum, 1) . '%"></i></span>';
}

function sms_report_render($r, $o)
{
    $f = $r['funnel'];
    $admin = !empty($o['admin']);
    echo '<div class="smsr">';
    sms_rv_filters($r, $o['self'], isset($o['sends_filter']) ? $o['sends_filter'] : null, isset($o['hidden']) ? $o['hidden'] : array());
    if ($f['total'] === 0) {
        echo '<div class="smsr-empty"><h2>No messages in this period</h2><p>Choose a longer period above' . ($admin ? '.' : ', or <a href="sms-portal.php">send your first SMS</a>.') . '</p></div></div>';
        return;
    }
    echo '<section class="smsr-insights" aria-label="What this means">';
    foreach (array_slice($r['insights'], 0, 4) as $in) {
        echo '<article class="smsr-insight is-' . sms_rv_e($in['tone']) . '"><h2>' . sms_rv_e($in['title']) . '</h2><p>' . sms_rv_e($in['text']) . '</p></article>';
    }
    echo '</section>';
    $rate = $r['rate'] === null ? '—' : $r['rate'] . '%';
    $rateNote = $r['rate'] === null ? 'Not confirmed yet' : 'of ' . number_format($f['delivered'] + $f['undelivered']) . ' confirmed';
    echo '<section class="smsr-kpis">'
        . '<div class="smsr-kpi"><span>Delivery rate</span><strong>' . sms_rv_e($rate) . '</strong><small>' . sms_rv_e($rateNote) . '</small></div>'
        . '<div class="smsr-kpi"><span>Typical delivery time</span><strong>' . sms_rv_e(sms_duration_label($r['median'])) . '</strong><small>' . ($r['p90'] !== null ? '9 in 10 within ' . sms_rv_e(sms_duration_label($r['p90'])) : 'Shown once confirmations arrive') . '</small></div>'
        . '<div class="smsr-kpi"><span>Messages</span><strong>' . sms_rv_num($f['total']) . '</strong><small>' . sms_rv_num($f['parts']) . ' SMS parts' . '</small></div>'
        . '<div class="smsr-kpi"><span>Refused</span><strong>' . ($r['rejected_rate'] === null ? '—' : sms_rv_e($r['rejected_rate'] . '%')) . '</strong><small>' . sms_rv_num($f['rejected']) . ' refused by the line</small></div></section>';
    echo '<section class="smsr-card"><h2>Where your messages ended up</h2>';
    sms_rv_funnel($f);
    echo '</section><section class="smsr-card"><h2>Day by day</h2>' . sms_rv_chart($r['series']) . '</section>';

    echo '<div class="smsr-two"><section class="smsr-card"><h2>By network</h2><p class="smsr-hint">Worked out from the first digits of each number. Ported numbers can differ.</p>';
    echo '<table class="smsr-table"><thead><tr><th>Network</th><th>Messages</th><th>Delivered</th><th></th></tr></thead><tbody>';
    foreach ($r['carriers'] as $c) {
        echo '<tr><th scope="row">' . sms_rv_e($c['name']) . '</th><td>' . sms_rv_num($c['total']) . '</td><td>' . ($c['rate'] === null ? '—' : sms_rv_e($c['rate'] . '%')) . '</td><td>' . sms_rv_bar($c['delivered'], $c['awaiting'], $c['undelivered'] + $c['rejected']) . '</td></tr>';
    }
    echo '</tbody></table></section><section class="smsr-card"><h2>Why messages did not go</h2>';
    if (!$r['reasons'] && $f['undelivered'] === 0) {
        echo '<p class="smsr-ok">Nothing was refused or undelivered in this period.</p>';
    }
    echo '<ul class="smsr-reasons">';
    if ($f['undelivered'] > 0) {
        echo '<li><div><b>Network could not deliver</b><span>' . sms_rv_num($f['undelivered']) . '</span></div><p>The phone was off, out of coverage, or the number is no longer active. Try again later or remove inactive numbers.</p></li>';
    }
    foreach ($r['reasons'] as $why) {
        echo '<li><div><b>' . sms_rv_e($why['title']) . '</b><span>' . sms_rv_num($why['count']) . '</span></div><p>' . sms_rv_e($why['help']) . '</p></li>';
    }
    echo '</ul></section></div>';

    if (!empty($r['sends'])) {
        echo '<section class="smsr-card"><h2>Sends in this period</h2><div class="smsr-scroll"><table class="smsr-table"><thead><tr><th>Send</th><th>Messages</th><th>Delivered</th><th>Progress</th><th></th></tr></thead><tbody>';
        foreach ($r['sends'] as $s) {
            $rate = sms_percent($s['delivered'], $s['delivered'] + $s['undelivered']);
            echo '<tr><th scope="row">' . sms_rv_e($s['name']) . '<small>' . sms_rv_e($s['at'] !== '' ? date('j M, H:i', strtotime($s['at'])) : '') . '</small></th><td>' . sms_rv_num($s['total']) . '</td><td>' . ($rate === null ? '—' : sms_rv_e($rate . '%')) . '</td><td>' . sms_rv_bar($s['delivered'], $s['awaiting'], $s['undelivered'] + $s['rejected']) . '</td>'
                . '<td><a class="smsr-link" href="' . sms_rv_e($o['logs'] . '?send=' . (int) $s['id']) . '">Messages</a></td></tr>';
        }
        echo '</tbody></table></div></section>';
    }
    if ($admin && !empty($o['clients'])) {
        echo '<section class="smsr-card"><h2>By client</h2><div class="smsr-scroll"><table class="smsr-table"><thead><tr><th>Client</th><th>Messages</th><th>Delivered</th><th>Refused</th></tr></thead><tbody>';
        foreach ($o['clients'] as $c) {
            echo '<tr><th scope="row"><a class="smsr-link" href="client.php?id=' . (int) $c['id'] . '">' . sms_rv_e($c['name']) . '</a></th><td>' . sms_rv_num($c['total']) . '</td><td>' . ($c['rate'] === null ? '—' : sms_rv_e($c['rate'] . '%')) . '</td><td>' . sms_rv_num($c['rejected']) . '</td></tr>';
        }
        echo '</tbody></table></div></section>';
    }
    echo '<p class="smsr-foot">';
    echo '<a class="sms-dash-btn" href="' . sms_rv_e($o['logs'] . '?export=1&from=' . $r['from'] . '&to=' . $r['to']) . '">Download messages (CSV)</a>';
    echo ' <a class="sms-dash-btn" href="' . sms_rv_e($o['logs'] . '?from=' . $r['from'] . '&to=' . $r['to']) . '">Open message list</a></p>';
    if ($admin && $r['confirmed_share'] !== null && $r['confirmed_share'] < 50) {
        echo '<p class="smsr-note">Only ' . (int) $r['confirmed_share'] . '% of accepted messages have a delivery confirmation. Check that the provider calls <code>/api/sms-dlr.php</code> (README: SMS delivery reports).</p>';
    }
    echo '</div>';
}
