<?php
/**
 * SMS: Talking to the upstream SMS provider and checking its stock.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

function sms_line($conn)
{
    $provider = billing_setting($conn, 'sms_line_provider');
    if ($provider !== 'aakash' && $provider !== 'sparrow') {
        $provider = '';
    }
    $mode = billing_setting($conn, 'sms_line_sender_mode');
    if ($mode !== 'approved') {
        $mode = 'fixed';
    }
    $sender = strtoupper(billing_plain_line(billing_setting($conn, 'sms_line_sender'), 11));
    if (!preg_match('/^[A-Z0-9]{3,11}$/', $sender)) {
        $sender = '';
    }
    return array(
        'provider' => $provider,
        'sender' => $sender,
        'sender_mode' => $mode,
        'connected' => $provider !== '' && billing_setting($conn, 'sms_line_token') !== ''
    );
}

function sms_line_secret($conn)
{
    $line = sms_line($conn);
    $endpoint = trim(billing_setting($conn, 'sms_line_endpoint'));
    if ($endpoint !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d+)?/[A-Za-z0-9_./-]*$#', $endpoint)) {
        $endpoint = '';
    }
    $line['token'] = billing_setting($conn, 'sms_line_token');
    $line['endpoint'] = $endpoint;
    return $line;
}

function sms_http_form($url, $fields, $timeout = 25)
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'body' => '');
    }
    $timeout = (int) $timeout;
    if ($timeout < 3) {
        $timeout = 3;
    }
    if ($timeout > 25) {
        $timeout = 25;
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if (!is_string($body)) {
        $body = '';
    }
    if (strlen($body) > 262144) {
        $body = substr($body, 0, 262144);
    }
    return array(
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $body
    );
}

function sms_http_json($url, $payload, $headers, $timeout = 25)
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'body' => '');
    }
    $timeout = (int) $timeout;
    if ($timeout < 3) {
        $timeout = 3;
    }
    if ($timeout > 25) {
        $timeout = 25;
    }
    $bodyIn = json_encode($payload);
    if ($bodyIn === false) {
        $bodyIn = '{}';
    }
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: application/json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyIn);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(8, $timeout));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if (!is_string($body)) {
        $body = '';
    }
    if (strlen($body) > 262144) {
        $body = substr($body, 0, 262144);
    }
    return array(
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'body' => $body
    );
}

function sms_aakash_invalid_rows($node, &$rows)
{
    if (!is_array($node)) {
        return;
    }
    if (isset($node['invalid']) && is_array($node['invalid'])) {
        foreach ($node['invalid'] as $item) {
            $rows[] = $item;
        }
    }
    foreach ($node as $item) {
        if (is_array($item)) {
            sms_aakash_invalid_rows($item, $rows);
        }
    }
}

function sms_aakash_v4_problem($json)
{
    if (!is_array($json)) {
        return 'line-rejected';
    }
    $messages = array();
    $failed = !empty($json['error']);
    if (!empty($json['errors']) && is_array($json['errors'])) {
        $failed = true;
        foreach ($json['errors'] as $error) {
            if (is_array($error) && isset($error['message'])) {
                $messages[] = strtolower((string) $error['message']);
            }
        }
    }
    if (isset($json['responses']) && is_array($json['responses'])) {
        foreach ($json['responses'] as $response) {
            if (!is_array($response) || empty($response['error'])) {
                continue;
            }
            $failed = true;
            if (isset($response['message'])) {
                $messages[] = strtolower((string) $response['message']);
            }
            if (!empty($response['errors']) && is_array($response['errors'])) {
                foreach ($response['errors'] as $error) {
                    if (is_array($error) && isset($error['message'])) {
                        $messages[] = strtolower((string) $error['message']);
                    }
                }
            }
        }
    }
    $joined = implode(' ', $messages);
    if (strpos($joined, 'balance') !== false || strpos($joined, 'credit') !== false) {
        return 'line-empty';
    }
    return $failed ? 'line-rejected' : '';
}

function sms_line_mobile($value)
{
    $digits = auth_mobile_number($value);
    if (!preg_match('/^9[78]\d{8}$/', $digits)) {
        return '';
    }
    return $digits;
}

function sms_aakash_rejected($json, $numbers)
{
    if (!is_array($json) || !empty($json['error'])) {
        return null;
    }
    $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : array();
    $invalid = isset($data['invalid']) && is_array($data['invalid']) ? $data['invalid'] : array();
    $valid = isset($data['valid']) && is_array($data['valid']) ? $data['valid'] : array();
    if (!$invalid && !$valid) {
        return array();
    }
    $rejected = array();
    foreach ($invalid as $item) {
        if (!is_array($item)) {
            continue;
        }
        $digits = sms_line_mobile(isset($item['mobile']) ? $item['mobile'] : '');
        if ($digits !== '') {
            $rejected[$digits] = $digits;
        }
    }
    foreach ($valid as $item) {
        if (!is_array($item)) {
            continue;
        }
        $digits = sms_line_mobile(isset($item['mobile']) ? $item['mobile'] : '');
        $status = isset($item['status']) ? strtolower((string) $item['status']) : '';
        if ($digits !== '' && ($status === 'aborted' || $status === 'invalid')) {
            $rejected[$digits] = $digits;
        }
    }
    $asked = array();
    foreach ($numbers as $number) {
        $asked[(string) $number] = true;
    }
    $out = array();
    foreach ($rejected as $digits) {
        if (isset($asked[$digits])) {
            $out[] = $digits;
        }
    }
    return $out;
}

function sms_vendor_send($conn, $numbers, $text, $sender, $timeout = 25)
{
    $line = sms_line_secret($conn);
    if ($line['provider'] === '' || $line['token'] === '') {
        return array('code' => 'line-off', 'rejected' => array());
    }
    $to = implode(',', $numbers);
    if ($line['provider'] === 'aakash') {
        $custom = $line['endpoint'];
        $useV4 = $custom === '' || strpos($custom, '/sms/v4/') !== false;
        if ($useV4) {
            $url = $custom !== '' ? $custom : 'https://sms.aakashsms.com/sms/v4/send-user';
            $response = sms_http_json($url, array(
                'to' => array_values($numbers),
                'text' => array($text)
            ), array('auth-token: ' . $line['token']), $timeout);
            $json = json_decode($response['body'], true);
            $problem = sms_aakash_v4_problem(is_array($json) ? $json : array());
            if ($response['ok'] && $problem === '') {
                $invalid = array();
                sms_aakash_invalid_rows(is_array($json) ? $json : array(), $invalid);
                $rejected = sms_aakash_rejected(array('data' => array('invalid' => $invalid, 'valid' => array())), $numbers);
                return array('code' => '', 'rejected' => is_array($rejected) ? $rejected : array());
            }
            return array('code' => $problem !== '' ? $problem : 'line-rejected', 'rejected' => array());
        }
        $url = $custom !== '' ? $custom : 'https://sms.aakashsms.com/sms/v3/send/';
        $response = sms_http_form($url, array(
            'auth_token' => $line['token'],
            'to' => $to,
            'text' => $text
        ), $timeout);
        $json = json_decode($response['body'], true);
        if ($response['ok'] && is_array($json) && empty($json['error'])) {
            $rejected = sms_aakash_rejected($json, $numbers);
            return array('code' => '', 'rejected' => is_array($rejected) ? $rejected : array());
        }
        $message = is_array($json) && isset($json['message']) ? strtolower((string) $json['message']) : '';
        if (strpos($message, 'balance') !== false || strpos($message, 'credit') !== false) {
            return array('code' => 'line-empty', 'rejected' => array());
        }
        return array('code' => 'line-rejected', 'rejected' => array());
    }
    $url = $line['endpoint'] !== '' ? $line['endpoint'] : 'http://api.sparrowsms.com/v2/sms/';
    $response = sms_http_form($url, array(
        'token' => $line['token'],
        'from' => $sender,
        'to' => $to,
        'text' => $text
    ), $timeout);
    $json = json_decode($response['body'], true);
    $code = is_array($json) && isset($json['response_code']) ? (int) $json['response_code'] : 0;
    if ($response['status'] === 200 && $code === 200) {
        return array('code' => '', 'rejected' => array());
    }
    if ($code === 1008) {
        return array('code' => 'sender-rejected', 'rejected' => array());
    }
    if ($code === 1012 || $code === 1013) {
        return array('code' => 'line-empty', 'rejected' => array());
    }
    return array('code' => 'line-rejected', 'rejected' => array());
}

function sms_find_balance($value)
{
    $wanted = array('credits_available', 'available_credit', 'credit', 'credits', 'balance', 'available');
    if (!is_array($value)) {
        return null;
    }
    foreach ($wanted as $key) {
        foreach ($value as $name => $item) {
            if (strtolower((string) $name) === $key && is_numeric($item)) {
                return (int) $item;
            }
        }
    }
    foreach ($value as $item) {
        if (is_array($item)) {
            $found = sms_find_balance($item);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

function sms_line_balance($conn)
{
    $line = sms_line_secret($conn);
    if ($line['provider'] === '' || $line['token'] === '') {
        return array('ok' => false, 'error' => 'Save the SMS line first.', 'balance' => null);
    }
    if ($line['provider'] === 'aakash') {
        $response = sms_http_json('https://sms.aakashsms.com/sms/v4/credit', new stdClass(), array('auth-token: ' . $line['token']));
        $json = json_decode($response['body'], true);
        $balance = is_array($json) ? sms_find_balance($json) : null;
        if ($balance === null) {
            $response = sms_http_form('https://sms.aakashsms.com/sms/v1/credit', array('auth_token' => $line['token']));
        }
    } else {
        $response = sms_http_form('http://api.sparrowsms.com/v2/credit/', array('token' => $line['token']));
    }
    $json = json_decode($response['body'], true);
    if (!is_array($json) || !empty($json['error'])) {
        return array('ok' => false, 'error' => 'The SMS line did not accept the token.', 'balance' => null);
    }
    $balance = sms_find_balance($json);
    if ($balance === null) {
        return array('ok' => false, 'error' => 'The SMS line answered, but no balance figure was included.', 'balance' => null);
    }
    return array('ok' => true, 'error' => '', 'balance' => $balance);
}

function sms_vendor_stock($conn, $force = false)
{
    $line = sms_line($conn);
    $label = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
    $saved = billing_setting($conn, 'sms_vendor_balance');
    $checked = billing_setting($conn, 'sms_vendor_checked');
    $balance = ($saved !== '' && is_numeric($saved)) ? (int) $saved : null;
    $age = $checked !== '' ? time() - (int) strtotime($checked) : 999999;
    if (!$line['connected']) {
        return array('balance' => null, 'checked' => '', 'error' => '', 'label' => '');
    }
    if (!$force && $balance !== null && $age >= 0 && $age < 900) {
        return array('balance' => $balance, 'checked' => $checked, 'error' => '', 'label' => $label);
    }
    $live = sms_line_balance($conn);
    if (!empty($live['ok'])) {
        $now = date('Y-m-d H:i:s');
        billing_set_setting($conn, 'sms_vendor_balance', (string) (int) $live['balance']);
        billing_set_setting($conn, 'sms_vendor_checked', $now);
        return array('balance' => (int) $live['balance'], 'checked' => $now, 'error' => '', 'label' => $label);
    }
    return array(
        'balance' => $balance,
        'checked' => $checked,
        'error' => (string) $live['error'],
        'label' => $label
    );
}

function sms_vendor_stock_saved($conn)
{
    $line = sms_line($conn);
    $saved = billing_setting($conn, 'sms_vendor_balance');
    $label = $line['provider'] === 'aakash' ? 'Aakash SMS' : ($line['provider'] === 'sparrow' ? 'Sparrow SMS' : '');
    if (!$line['connected'] || $saved === '' || !is_numeric($saved)) {
        return array('balance' => null, 'label' => $label);
    }
    return array('balance' => (int) $saved, 'label' => $label);
}

function sms_line_test($conn, $number)
{
    $digits = auth_mobile_number($number);
    if (!preg_match('/^9[78]\d{8}$/', $digits)) {
        return 'Enter one 10-digit Nepal mobile for the check.';
    }
    $line = sms_line($conn);
    if (!$line['connected']) {
        return 'Save the API key first.';
    }
    if ($line['provider'] === 'sparrow' && $line['sender'] === '') {
        return 'Save the sender name on the Sparrow account first.';
    }
    $checked = sms_vendor_send($conn, array($digits), 'Aakash Technologies line check.', $line['sender']);
    if ($checked['code'] !== '' || $checked['rejected']) {
        return 'The line did not accept the check. Confirm the API key and that the bought account still has credit.';
    }
    return '';
}

function sms_save_line($conn, $post)
{
    $provider = isset($post['sms_line_provider']) ? (string) $post['sms_line_provider'] : '';
    if ($provider !== 'aakash' && $provider !== 'sparrow') {
        $provider = '';
    }
    $sender = strtoupper(billing_plain_line(isset($post['sms_line_sender']) ? $post['sms_line_sender'] : '', 11));
    if ($provider === 'sparrow' && !preg_match('/^[A-Z0-9]{3,11}$/', $sender)) {
        return 'Enter the sender name registered on the Sparrow account, 3 to 11 letters or numbers.';
    }
    if ($provider === 'aakash') {
        $sender = '';
    }
    $mode = (isset($post['sms_line_sender_mode']) && $post['sms_line_sender_mode'] === 'approved') ? 'approved' : 'fixed';
    if ($provider !== 'sparrow') {
        $mode = 'fixed';
    }
    $endpoint = trim(isset($post['sms_line_endpoint']) ? (string) $post['sms_line_endpoint'] : '');
    if ($endpoint !== '' && !preg_match('#^https?://[A-Za-z0-9.-]+(?::\d+)?/[A-Za-z0-9_./-]*$#', $endpoint)) {
        return 'The send address has to be an http or https URL, or leave it empty.';
    }
    $token = trim(isset($post['sms_line_token']) ? (string) $post['sms_line_token'] : '');
    $token = str_replace(array("\r", "\n", " "), '', $token);
    if ($provider !== '' && $token === '' && billing_setting($conn, 'sms_line_token') === '') {
        return 'Paste the API key from the account you buy SMS from.';
    }
    if ($token !== '' && (strlen($token) < 8 || strlen($token) > 200)) {
        return 'That API key does not look complete.';
    }
    billing_set_setting($conn, 'sms_line_provider', $provider);
    billing_set_setting($conn, 'sms_line_sender', $sender);
    billing_set_setting($conn, 'sms_line_sender_mode', $mode);
    billing_set_setting($conn, 'sms_line_endpoint', $endpoint);
    if ($token !== '') {
        billing_set_setting($conn, 'sms_line_token', $token);
    }
    if ($provider === '') {
        billing_set_setting($conn, 'sms_line_token', '');
    }
    return '';
}
