<?php
/**
 * Admin dashboard: everything that waits for a person, in the order to do it.
 * Pure functions so the order and wording can be tested. Money and blocked clients come first.
 */

function admin_waiting_since($conn, $sql)
{
    try {
        $result = $conn->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        $value = $row ? array_values($row)[0] : null;
        return $value ? (string) $value : '';
    } catch (Throwable $exception) {
        return '';
    }
}

/** "waiting since" words: today, 1 day, 3 days. */
function admin_age_words($since, $now = null)
{
    if ($since === '' || strtotime($since) === false) {
        return '';
    }
    $now = $now === null ? time() : $now;
    $days = (int) floor(($now - strtotime($since)) / 86400);
    if ($days <= 0) {
        return 'since today';
    }
    return 'oldest ' . $days . ' day' . ($days === 1 ? '' : 's');
}

/**
 * $c: counts keyed by name, $since: oldest waiting time keyed by name.
 * Each item: key, count, tone (bad, warn, info), title, help, href, age.
 */
function admin_attention_items($c, $since = array(), $now = null)
{
    $n = function ($key) use ($c) {
        return isset($c[$key]) ? (int) $c[$key] : 0;
    };
    $age = function ($key) use ($since, $now) {
        return isset($since[$key]) ? admin_age_words($since[$key], $now) : '';
    };
    $plural = function ($count, $one, $many) {
        return $count === 1 ? $one : $many;
    };
    $items = array();
    $add = function ($key, $count, $tone, $title, $help, $href) use (&$items, $age) {
        if ($count > 0) {
            $items[] = array('key' => $key, 'count' => $count, 'tone' => $tone, 'title' => $title, 'help' => $help, 'href' => $href, 'age' => $age($key));
        }
    };
    $add('sms_short', $n('sms_short'), 'bad', 'SMS stock is short by ' . number_format($n('sms_short')), 'Clients hold ' . number_format($n('sms_holding')) . ' SMS and the bulk line has less. Buy more from the vendor before sends fail.', 'sms-line.php?tab=line');
    $add('topups', $n('topups'), 'warn', $n('topups') . ' wallet top-up' . $plural($n('topups'), '', 's') . ' to confirm', 'Clients have paid and are waiting for the amount to appear. Renewals after that run by themselves.', 'billing.php?tab=wallet');
    $add('kyc', $n('kyc'), 'warn', $n('kyc') . ' ' . $plural($n('kyc'), 'identity', 'identities') . ' to check', 'SMS, voice jobs and API tokens stay closed for these clients until you approve.', 'kyc.php');
    $add('domains', $n('domains'), 'warn', $n('domains') . ' paid domain' . $plural($n('domains'), '', 's') . ' to register', 'Register the name, then mark it active.', 'domains.php');
    $add('booked', $n('booked'), 'info', $n('booked') . ' website or training booking' . $plural($n('booked'), '', 's'), 'The brief is under Billing, Delivery.', 'billing.php?tab=delivery');
    $add('hosting', $n('hosting'), 'info', $n('hosting') . ' hosting plan' . $plural($n('hosting'), ' needs', 's need') . ' a cPanel login', 'Add it under Billing, Delivery.', 'billing.php?tab=delivery');
    $add('mail', $n('mail'), 'info', $n('mail') . ' mailbox plan' . $plural($n('mail'), ' needs', 's need') . ' its login', 'Add the login the client will see under Billing, Delivery.', 'billing.php?tab=delivery');
    $add('voice', $n('voice'), 'info', $n('voice') . ' voice job' . $plural($n('voice'), '', 's') . ' to place', 'Place the call, then mark it placed.', 'campaigns.php');
    $add('senders', $n('senders'), 'info', $n('senders') . ' sender name' . $plural($n('senders'), '', 's') . ' to approve', 'Clients cannot send under that name until it is approved.', 'sms-line.php?tab=names');
    $add('tickets', $n('tickets'), 'warn', $n('tickets') . ' support ticket' . $plural($n('tickets'), '', 's') . ' waiting', 'Open and in progress.', 'tickets.php');
    $add('inquiries', $n('inquiries'), 'info', $n('inquiries') . ' new inquir' . $plural($n('inquiries'), 'y', 'ies'), 'From the website contact form. Answer while the visitor is still interested.', 'inquiries.php');
    $add('renewals', $n('renewals'), 'info', $n('renewals') . ' renewal' . $plural($n('renewals'), '', 's') . ' within 14 days', 'The client wallet must cover them. See Billing, Records.', 'billing.php?tab=records');
    return $items;
}

function admin_attention_render($items)
{
    $e = function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    if (!$items) {
        echo '<section class="adq adq-clear" aria-label="Needs your attention"><h2>Nothing is waiting</h2><p>Top-ups, identities, tickets and orders are all dealt with.</p></section>';
        return;
    }
    $tones = array('bad' => 0, 'warn' => 0, 'info' => 0);
    foreach ($items as $item) {
        $tones[$item['tone']]++;
    }
    echo '<section class="adq" aria-labelledby="adq-h"><div class="adq-head"><h2 id="adq-h">Needs your attention</h2><span>' . count($items) . ' thing' . (count($items) === 1 ? '' : 's') . ', most urgent first</span></div><ol class="adq-list">';
    foreach ($items as $item) {
        echo '<li class="is-' . $e($item['tone']) . '"><a href="' . $e($item['href']) . '"><b class="adq-count">' . number_format($item['count']) . '</b><span class="adq-text"><strong>' . $e($item['title']) . '</strong><small>' . $e($item['help']) . '</small></span>'
            . ($item['age'] !== '' ? '<em>' . $e($item['age']) . '</em>' : '') . '<i aria-hidden="true">&rsaquo;</i></a></li>';
    }
    echo '</ol></section>';
}
