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
