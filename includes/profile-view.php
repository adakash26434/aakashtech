<?php
/** Profile: what is still missing on an account, in the order a client should do it. No output. */

function profile_checklist($client, $kycStatus)
{
    $kyc = array(
        'approved' => array(true, 'Identity verified', 'You can send SMS without the 100-message limit.', 'kyc.php'),
        'pending' => array(false, 'Identity is being checked', 'We will email you when it is done.', 'kyc.php'),
        'rejected' => array(false, 'Identity needs a change', 'Open it to see what to fix.', 'kyc.php')
    );
    $identity = isset($kyc[$kycStatus]) ? $kyc[$kycStatus] : array(false, 'Verify your identity', 'Needed to send more than 100 SMS.', 'kyc.php');
    $items = array(
        array('done' => $identity[0], 'title' => $identity[1], 'help' => $identity[2], 'href' => $identity[3]),
        array('done' => trim((string) ($client['address'] ?? '')) !== '', 'title' => 'Address saved', 'help' => 'Invoices and receipts use it.', 'href' => '#profile-form'),
        array('done' => trim((string) ($client['company'] ?? '')) !== '', 'title' => 'Company or organization name saved', 'help' => 'Shown on your invoices. Leave empty if you are an individual.', 'href' => '#profile-form')
    );
    $done = 0;
    foreach ($items as $item) {
        $done += $item['done'] ? 1 : 0;
    }
    return array('items' => $items, 'done' => $done, 'total' => count($items), 'percent' => (int) round($done * 100 / count($items)));
}

/** Rough password quality for the meter. The server rule is unchanged: at least 8 characters. */
function profile_password_score($password)
{
    $password = (string) $password;
    $score = 0;
    $length = strlen($password);
    $score += $length >= 8 ? 1 : 0;
    $score += $length >= 12 ? 1 : 0;
    $score += preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password) ? 1 : 0;
    $score += preg_match('/\d/', $password) ? 1 : 0;
    $score += preg_match('/[^A-Za-z0-9]/', $password) ? 1 : 0;
    return min(4, $score);
}
