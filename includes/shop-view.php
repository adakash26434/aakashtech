<?php
/**
 * Buy-a-service helpers: numbers and wording for the plan cards. Pure functions, no output,
 * so the checkout and My Services pages can reuse them.
 */

function shop_cycle_chip($cycle, $autoRenew)
{
    if ($cycle === 'yearly') {
        return $autoRenew ? 'Every year, auto-renews' : 'Every year';
    }
    if ($cycle === 'monthly') {
        return $autoRenew ? 'Every month, auto-renews' : 'Every month';
    }
    return 'One-time payment';
}

/** Everything a plan card needs: what it costs, the saving, the VAT bill and whether the wallet covers it. */
function shop_plan_facts($plan, $walletBalance)
{
    $regular = (float) $plan['price'];
    $offer = isset($plan['offer_price']) ? (float) $plan['offer_price'] : 0.0;
    $selling = billing_selling_price($regular, $offer);
    $hasOffer = $regular > 0 && $selling > 0 && $selling < $regular;
    $bill = billing_vat_bill($selling);
    $facts = array(
        'priced' => $regular > 0,
        'selling' => $selling,
        'regular' => $regular,
        'offer' => $hasOffer,
        'save_percent' => $hasOffer ? (int) round(($regular - $selling) * 100 / $regular) : 0,
        'bill' => $bill,
        'covered' => $regular > 0 && (float) $walletBalance + 0.001 >= $bill['total'],
        'short_by' => $regular > 0 ? max(0.0, round($bill['total'] - (float) $walletBalance, 2)) : 0.0,
        'per_unit' => null,
        'per_unit_label' => ''
    );
    $qty = (int) (isset($plan['unit_quantity']) ? $plan['unit_quantity'] : 0);
    if ($regular > 0 && $qty >= 1 && isset($plan['unit_kind']) && $plan['unit_kind'] === 'mailbox') {
        $facts['per_unit'] = round($selling / $qty, 2);
        $facts['per_unit_label'] = 'per mailbox';
    }
    return $facts;
}

/** The plan with the lowest cost per unit in a group, only when there are at least two to compare. */
function shop_best_value_code($plans)
{
    $best = '';
    $lowest = null;
    $count = 0;
    foreach ($plans as $plan) {
        $facts = shop_plan_facts($plan, 0);
        if ($facts['per_unit'] === null) {
            continue;
        }
        $count++;
        if ($lowest === null || $facts['per_unit'] < $lowest) {
            $lowest = $facts['per_unit'];
            $best = (string) $plan['code'];
        }
    }
    return $count >= 2 ? $best : '';
}

/** Services that need an approved identity before large sends. */
function shop_identity_note($conn, $clientId, $slugs)
{
    $needs = in_array('bulk-sms', $slugs, true) || in_array('bulk-voice', $slugs, true);
    if (!$needs || billing_kyc_approved($conn, $clientId)) {
        return '';
    }
    return 'Your identity is not approved yet. Until it is, you can send up to ' . number_format(sms_unverified_cap()) . ' SMS. Buying credits is fine; approve your identity to use them all.';
}

/** Plain-words status for a service: label, colour tone and a sentence that says what is happening. */
function service_status_info($status, $renewable)
{
    $map = array(
        'active' => array('Active', 'ok', 'Running.'),
        'booked' => array('Booked', 'info', 'The team is working on this from your details.'),
        'past_due' => array('Needs funds', 'warn', 'The renewal is waiting for wallet funds. It tries again by itself.'),
        'suspended' => array('Paused', 'bad', $renewable ? 'Paused until the wallet can cover the renewal. No request to staff is needed.' : 'Paused.'),
        'cancelled' => array('Ended', 'none', 'This service has ended.'),
        'expired' => array('Ended', 'none', 'This service has ended.')
    );
    $info = isset($map[$status]) ? $map[$status] : array(ucfirst(str_replace('_', ' ', (string) $status)), 'none', '');
    return array('label' => $info[0], 'tone' => $info[1], 'text' => $info[2]);
}

/** Which tab a service belongs to. */
function service_group($status)
{
    if ($status === 'active') {
        return 'active';
    }
    if ($status === 'booked') {
        return 'waiting';
    }
    if ($status === 'past_due' || $status === 'suspended') {
        return 'attention';
    }
    return 'ended';
}

/** Whole days from today to a date (negative when past). */
function service_days_until($date)
{
    return (int) floor((strtotime((string) $date) - strtotime(date('Y-m-d'))) / 86400);
}

/** Auto-renewals due soon: what they add up to, and whether the wallet covers them. */
function service_renewal_summary($rows, $walletBalance, $withinDays = 30)
{
    $due = array();
    $total = 0.0;
    foreach ($rows as $row) {
        if ((int) $row['auto_renew'] !== 1 || !in_array((string) $row['status'], array('active', 'past_due'), true) || empty($row['next_renewal']) || (float) $row['price'] <= 0) {
            continue;
        }
        $days = service_days_until($row['next_renewal']);
        if ($days <= $withinDays) {
            $due[] = array('name' => (string) $row['service_name'], 'date' => (string) $row['next_renewal'], 'days' => $days, 'amount' => (float) $row['price']);
            $total += (float) $row['price'];
        }
    }
    usort($due, function ($a, $b) {
        return strcmp($a['date'], $b['date']);
    });
    return array('items' => $due, 'total' => round($total, 2), 'short_by' => max(0.0, round($total - (float) $walletBalance, 2)), 'days' => $withinDays);
}

/** Words for a wallet row: what it was, in a sentence a person would say. */
function wallet_entry_words($entry)
{
    $kind = (string) $entry['kind'];
    $note = trim((string) $entry['reference_note']);
    $titles = array('topup' => 'Money added', 'purchase' => 'Bought a service', 'renewal' => 'Renewal paid', 'refund' => 'Money returned');
    $title = isset($titles[$kind]) ? $titles[$kind] : ucfirst($kind);
    $status = (string) $entry['status'];
    $states = array('completed' => array('Done', 'ok'), 'pending' => array('Waiting for confirmation', 'warn'), 'rejected' => array('Not accepted', 'bad'), 'reversed' => array('Reversed', 'none'));
    $state = isset($states[$status]) ? $states[$status] : array(ucfirst($status), 'none');
    return array('title' => $title, 'note' => $note, 'state' => $state[0], 'tone' => $state[1], 'in' => (string) $entry['direction'] === 'credit');
}

/** Totals for the rows on screen: money in and out, counting only finished rows, and what is still waiting. */
function wallet_totals($entries)
{
    $in = 0.0;
    $out = 0.0;
    $waiting = 0.0;
    foreach ($entries as $entry) {
        $amount = (float) $entry['amount'];
        if ($entry['status'] === 'pending' && $entry['direction'] === 'credit') {
            $waiting += $amount;
        } elseif ($entry['status'] === 'completed') {
            if ($entry['direction'] === 'credit') {
                $in += $amount;
            } else {
                $out += $amount;
            }
        }
    }
    return array('in' => round($in, 2), 'out' => round($out, 2), 'waiting' => round($waiting, 2));
}

/** Quick top-up amounts: round numbers, plus the exact sum that covers the coming renewals if the wallet is short. */
function wallet_quick_amounts($shortBy)
{
    $amounts = array(1000, 2000, 5000, 10000, 25000);
    $chips = array();
    if ($shortBy > 0) {
        $chips[] = array('amount' => (int) ceil($shortBy), 'label' => 'NPR ' . number_format((int) ceil($shortBy)), 'note' => 'covers your coming renewals');
    }
    foreach ($amounts as $amount) {
        if ($shortBy <= 0 || $amount !== (int) ceil($shortBy)) {
            $chips[] = array('amount' => $amount, 'label' => 'NPR ' . number_format($amount), 'note' => '');
        }
    }
    return $chips;
}
