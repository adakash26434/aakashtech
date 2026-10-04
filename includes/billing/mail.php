<?php
/**
 * Billing: E-mail catalog, sending and admin notices.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_mail_ok($email)
{
    $email = trim((string) $email);
    if ($email === '' || strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return (bool) preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email);
}

function site_official_email()
{
    return 'info@aakashtechnologies.com.np';
}

function site_sender_email()
{
    return 'noreply@aakashtechnologies.com.np';
}

function site_sender_name()
{
    return 'Aakash Tech';
}

function site_email_or_official($email)
{
    $email = strtolower(trim((string) $email));
    if ($email === '' || $email === 'info@aakashtechnologies.com') {
        return site_official_email();
    }
    return $email;
}

function billing_notify_address($conn)
{
    $key = 'notify_email';
    $stmt = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    if (!$stmt) {
        return site_official_email();
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return site_official_email();
    }
    $email = site_email_or_official($row['setting_value']);
    if (trim((string) $row['setting_value']) === '') {
        return '';
    }
    return billing_mail_ok($email) ? $email : '';
}

function billing_mail_from_address($conn)
{
    $saved = strtolower(trim(billing_setting($conn, 'mail_from')));
    if (billing_mail_ok($saved)) {
        return $saved;
    }
    return site_sender_email();
}

function billing_mail_reply_address($conn)
{
    $public = site_public_settings($conn);
    $email = isset($public['site_email']) ? (string) $public['site_email'] : '';
    $email = site_email_or_official($email);
    return billing_mail_ok($email) ? $email : site_official_email();
}

function billing_notify_clip($value, $max)
{
    $value = trim(str_replace(array("\r", "\n"), ' ', (string) $value));
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function billing_notify_client_label($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT name, email, phone FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 'Client ' . $clientId;
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return 'Client ' . $clientId;
    }
    return trim($row['name'] . ' · ' . $row['email'] . ($row['phone'] !== '' ? ' · ' . $row['phone'] : ''));
}

function billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo = '')
{
    $to = trim((string) $to);
    $from = trim((string) $from);
    $replyTo = trim((string) $replyTo);
    if (!billing_mail_ok($replyTo)) {
        $replyTo = $from;
    }
    if (!billing_mail_ok($to) || !billing_mail_ok($from)) {
        return array('ok' => false, 'error' => 'Save a notification email and a sending address first.');
    }
    $subject = billing_notify_clip($subject, 140);
    if ($subject === '') {
        $subject = 'New request';
    }
    $body = str_replace("\r", '', (string) $body);
    $fromName = trim(str_replace(array("\r", "\n", '"'), '', (string) $fromName));
    $fromHeader = $fromName === '' ? $from : '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nFrom: " . $fromHeader . "\r\nReply-To: " . $replyTo;
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $sent = @mail($to, $encodedSubject, $body, $headers, '-f' . $from);
    if (!$sent) {
        return array('ok' => false, 'error' => 'This server did not accept the email. On cPanel, noreply@aakashtechnologies.com.np should be a mailbox on this domain.');
    }
    return array('ok' => true, 'error' => '');
}

function billing_notify_send($conn, $subject, $lines, $isTest)
{
    $to = billing_notify_address($conn);
    if ($to === '') {
        return array('ok' => false, 'error' => 'No notification email is saved.');
    }
    $from = billing_mail_from_address($conn);
    $replyTo = billing_mail_reply_address($conn);
    $fromName = site_sender_name();
    $body = is_array($lines) ? implode("\n", $lines) : (string) $lines;
    $result = billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo);
    $stamp = date('Y-m-d H:i');
    if ($isTest) {
        billing_set_setting($conn, 'notify_test_at', $stamp);
        billing_set_setting($conn, 'notify_test_result', $result['ok'] ? 'accepted' : 'failed');
        billing_set_setting($conn, 'notify_test_error', $result['error']);
    } else {
        billing_set_setting($conn, 'notify_last_at', $stamp);
        billing_set_setting($conn, 'notify_last_result', $result['ok'] ? 'accepted' : 'failed');
        billing_set_setting($conn, 'notify_last_error', $result['error']);
    }
    return $result;
}

function billing_notify($conn, $subject, $lines)
{
    try {
        return billing_notify_send($conn, $subject, $lines, false);
    } catch (Throwable $exception) {
        error_log('Request email could not be sent.');
        return array('ok' => false, 'error' => 'The email could not be sent.');
    }
}

function billing_client_email($conn, $clientId)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT email FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    $email = $row ? trim((string) $row['email']) : '';
    return billing_mail_ok($email) ? $email : '';
}

function billing_mail_person($conn, $to, $subject, $lines)
{
    try {
        $from = billing_mail_from_address($conn);
        $replyTo = billing_mail_reply_address($conn);
        $fromName = site_sender_name();
        $body = is_array($lines) ? implode("\n", $lines) : (string) $lines;
        return billing_mail_send($to, $subject, $body, $from, $fromName, $replyTo);
    } catch (Throwable $exception) {
        error_log('Client email could not be sent.');
        return array('ok' => false, 'error' => 'The email could not be sent.');
    }
}

function billing_mail_catalog()
{
    return array(
        'account' => array(
            'when' => 'Someone registers, or you create the account in Clients',
            'subject' => 'Your account is ready',
            'lines' => array(
                'Your account is ready.',
                'Sign in to the client portal with this email address.',
                'Use the password you chose, or the password the team gave you. This email does not contain the password.'
            )
        ),
        'paid' => array(
            'when' => 'The client pays from the wallet and the service starts now',
            'subject' => 'Payment received: {service}',
            'lines' => array(
                'Your payment of NPR {amount} is complete.',
                'Service: {service}',
                'It is active on your account. Open My Services in the client portal.'
            )
        ),
        'booked' => array(
            'when' => 'The client pays for a website or a training visit',
            'subject' => 'Booking received: {service}',
            'lines' => array(
                'Your booking is saved.',
                'Payment of NPR {amount} is complete.',
                'Service: {service}',
                'The team will confirm the next step. Open My Services to see the date, place, or website link when it is ready.'
            )
        ),
        'sms-ready' => array(
            'when' => 'You add SMS credits on a client account',
            'subject' => 'SMS credits are on your account',
            'lines' => array(
                '{credits} SMS credits are on your account.',
                'Open Send SMS in the client portal and write the message.',
                'Note: {note}'
            )
        ),
        'office-service' => array(
            'when' => 'You add a service that was sold at the office',
            'subject' => 'Added to your account: {service}',
            'lines' => array(
                'The team added this to your account.',
                'Service: {service}',
                'Status: {status}',
                'Payment was taken outside the wallet.',
                'Open My Services in the client portal.'
            )
        ),
        'topup-waiting' => array(
            'when' => 'The client submits a wallet top-up',
            'subject' => 'Wallet payment received',
            'lines' => array(
                'We received a wallet top-up of NPR {amount}.',
                'It stays pending until the payment reference is confirmed.',
                'The amount is not in the wallet yet.'
            )
        ),
        'topup-done' => array(
            'when' => 'You confirm that top-up',
            'subject' => 'Wallet payment confirmed',
            'lines' => array(
                'NPR {amount} is now in your wallet.',
                'You can pay for a service from the client portal.'
            )
        ),
        'sms-low' => array(
            'when' => 'A client\'s SMS credits drop below 100 (sent at most once a day)',
            'subject' => 'Your SMS credits are running low',
            'lines' => array(
                'You have {left} SMS credits left.',
                'Buy more from the client portal before your next send is refused.'
            )
        ),
        'office-wallet' => array(
            'when' => 'You record a cash or office payment on the wallet',
            'subject' => 'Wallet payment recorded',
            'lines' => array(
                'NPR {amount} was added to your wallet.',
                'Note: {note}'
            )
        ),
        'topup-rejected' => array(
            'when' => 'You reject a wallet top-up',
            'subject' => 'Wallet payment was not added',
            'lines' => array(
                'The wallet top-up of NPR {amount} was not confirmed.',
                'It was not added to the wallet.',
                'If the money already left your account, reply to this email.'
            )
        ),
        'domain-request' => array(
            'when' => 'A domain name is requested',
            'subject' => 'Domain request received: {domain}',
            'lines' => array(
                'We saved the request for {domain}.',
                'The yearly bill is NPR {amount}.',
                'Pay it from the wallet when the balance covers the year. Registration starts after that payment is confirmed.'
            )
        ),
        'domain-paid' => array(
            'when' => 'The client pays the domain year from the wallet',
            'subject' => 'Domain payment received: {domain}',
            'lines' => array(
                'NPR {amount} was taken from the wallet for {domain}.',
                'The name is waiting to be registered. You will get another email when it is active.'
            )
        ),
        'domain-active' => array(
            'when' => 'You mark a domain active',
            'subject' => 'Domain active: {domain}',
            'lines' => array(
                '{domain} is marked Active.',
                'The paid year starts now and renews from the wallet.',
                'See it under My domains in the client panel.'
            )
        ),
        'domain-closed' => array(
            'when' => 'You close a domain request without registering it',
            'subject' => 'Domain request closed: {domain}',
            'lines' => array(
                'The domain request for {domain} was not registered.',
                '{refund}',
                'Note: {note}'
            )
        ),
        'renewed' => array(
            'when' => 'A monthly or yearly service renews from the wallet',
            'subject' => 'Renewal paid: {service}',
            'lines' => array(
                'NPR {amount} was taken from the wallet.',
                'Service: {service}',
                'It continues through {next}.'
            )
        ),
        'renewal-waiting' => array(
            'when' => 'A renewal cannot be paid because the wallet is short',
            'subject' => 'Renewal is waiting: {service}',
            'lines' => array(
                '{service} could not renew today.',
                'Add at least NPR {amount} to the wallet.',
                'It tries again automatically. After the grace period the service pauses until the wallet can cover it.'
            )
        ),
        'password-office' => array(
            'when' => 'You set a new password for a client',
            'subject' => 'Your password was changed',
            'lines' => array(
                'The team set a new password on your account.',
                'Sign in with the password they gave you. It is not written in this email.',
                'After you sign in, you can change it from Profile.'
            )
        ),
        'password' => array(
            'when' => 'The client asks to reset a password',
            'subject' => 'Reset your password',
            'lines' => array(
                'Use the link in the email to choose a new password. It works for 30 minutes.',
                'If you did not ask for this, ignore the email. The current password stays in place.'
            )
        ),
        'website-live' => array(
            'when' => 'You publish a booked website',
            'subject' => 'Your website is live: {service}',
            'lines' => array(
                'Your website is published.',
                'Open it from My Services, or go to {url}.',
                '{note}'
            )
        ),
        'training-set' => array(
            'when' => 'You confirm or complete a training visit',
            'subject' => 'Visit update: {service}',
            'lines' => array(
                'Your visit is {status}.',
                'Date: {date}',
                '{place}'
            )
        ),
        'hosting-ready' => array(
            'when' => 'You turn on a hosting login',
            'subject' => 'Hosting login is ready',
            'lines' => array(
                'The hosting login is ready in My Services.',
                'Username: {user}',
                'The password is the one the team sent. It is not written in this email.'
            )
        ),
        'mailbox-ready' => array(
            'when' => 'You turn on mailbox login',
            'subject' => 'Mailbox login is ready',
            'lines' => array(
                'Your mailbox login is ready.',
                'Addresses: {boxes}',
                'Open the inbox from My Services. The password is the one the team sent. It is not written in this email.'
            )
        ),
        'suspended' => array(
            'when' => 'A service pauses because the wallet stayed short',
            'subject' => 'Service paused: {service}',
            'lines' => array(
                '{service} is paused.',
                'The wallet did not cover NPR {amount} before the grace period ended.',
                'Add funds and it resumes on the next renewal check.'
            )
        ),
        'refund' => array(
            'when' => 'A domain order is returned to the wallet',
            'subject' => 'Amount returned to your wallet',
            'lines' => array(
                'NPR {amount} was returned to your wallet.',
                'Reason: {note}'
            )
        ),
        'enquiry' => array(
            'when' => 'A visitor sends the public contact form',
            'subject' => 'We received your message',
            'lines' => array(
                'We received your message from the website.',
                'A reply will come to this email address.'
            )
        ),
        'ticket-opened' => array(
            'when' => 'The client opens a support ticket',
            'subject' => 'Support ticket received',
            'lines' => array(
                'We received your support ticket: {subject}',
                'A reply will come by email and under Support in the client portal.'
            )
        ),
        'ticket-reply' => array(
            'when' => 'You reply to a support ticket',
            'subject' => 'Reply on your support ticket',
            'lines' => array(
                'There is a reply on: {subject}',
                '{reply}',
                'Open Support in the client portal to read it.'
            )
        ),
        'kyc-received' => array(
            'when' => 'The client submits identity details',
            'subject' => 'Identity details received',
            'lines' => array(
                'We received your identity details.',
                'SMS and voice stay closed until they are approved. You will get an email when that decision is made.'
            )
        ),
        'kyc-approved' => array(
            'when' => 'You approve identity details',
            'subject' => 'Identity approved',
            'lines' => array(
                'Your identity is approved.',
                'You can send SMS from the SMS dashboard. A voice job is saved under Messages, and the team places the call.'
            )
        ),
        'kyc-change' => array(
            'when' => 'You send identity details back for a change',
            'subject' => 'Identity needs a change',
            'lines' => array(
                'The identity submission was sent back.',
                'Note: {note}',
                'Update it in the client panel and submit again.'
            )
        ),
        'contact-email' => array(
            'when' => 'You change the client sign-in email',
            'subject' => 'Sign-in email changed',
            'lines' => array(
                'The team changed the sign-in email for this account.',
                'Sign in with {email}.',
                'The password stays the same. This email does not contain it.'
            )
        ),
        'contact-phone' => array(
            'when' => 'You change the client mobile number',
            'subject' => 'Mobile number changed',
            'lines' => array(
                'The team changed the mobile number on your account to {phone}.'
            )
        ),
        'login' => array(
            'when' => 'The client finishes signing in',
            'subject' => 'Sign-in on your account',
            'lines' => array(
                'A sign-in to your account was just completed.',
                'User ID: {id}',
                'Email: {email}',
                'IP address: {ip}',
                'Nepal time: {when}',
                'If you did not sign in, contact {contact} or open a support ticket. This email does not contain the password.'
            )
        )
    );
}

function billing_mail_fill($text, $map)
{
    $text = (string) $text;
    if (!is_array($map)) {
        return $text;
    }
    foreach ($map as $key => $value) {
        $text = str_replace('{' . $key . '}', (string) $value, $text);
    }
    return $text;
}

function billing_mail_to_client($conn, $clientId, $subject, $lines)
{
    $clientId = (int) $clientId;
    $stmt = $conn->prepare('SELECT name, email FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return array('ok' => false, 'error' => 'The client could not be read.');
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    $email = $row ? trim((string) $row['email']) : '';
    if (!billing_mail_ok($email)) {
        return array('ok' => false, 'error' => 'That client has no email address.');
    }
    $name = $row && trim((string) $row['name']) !== '' ? trim((string) $row['name']) : 'there';
    $site = site_sender_name();
    $body = array('Hello ' . $name . ',', '');
    foreach ((array) $lines as $line) {
        $body[] = (string) $line;
    }
    $body[] = '';
    $body[] = $site;
    return billing_mail_person($conn, $email, $subject, $body);
}

function billing_mail_client_event($conn, $clientId, $key, $map = array())
{
    $catalog = billing_mail_catalog();
    if (!isset($catalog[$key])) {
        return array('ok' => false, 'error' => 'That email is not defined.');
    }
    $item = $catalog[$key];
    $lines = array();
    foreach ($item['lines'] as $line) {
        $filled = trim(billing_mail_fill($line, $map));
        if ($filled !== '') {
            $lines[] = $filled;
        }
    }
    return billing_mail_to_client($conn, $clientId, billing_mail_fill($item['subject'], $map), $lines);
}

function billing_client_login_notice($conn, $clientId)
{
    $clientId = (int) $clientId;
    if ($clientId < 1) {
        return;
    }
    $stmt = $conn->prepare('SELECT id, email, login_notice_at, login_notice_ip FROM client_users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $row = db_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) {
        return;
    }
    $ip = function_exists('auth_client_ip') ? auth_client_ip() : '';
    if ($ip === '') {
        $ip = 'unknown';
    }
    $previousAt = isset($row['login_notice_at']) ? strtotime((string) $row['login_notice_at']) : false;
    $previousIp = isset($row['login_notice_ip']) ? (string) $row['login_notice_ip'] : '';
    if ($previousAt && $previousIp === $ip && (time() - $previousAt) < 1800) {
        return;
    }
    $when = new DateTime('now', new DateTimeZone('Asia/Kathmandu'));
    $stamp = $when->format('Y-m-d H:i');
    $public = site_public_settings($conn);
    $contact = isset($public['site_email']) && trim((string) $public['site_email']) !== '' ? trim((string) $public['site_email']) : site_official_email();
    billing_mail_client_event($conn, $clientId, 'login', array(
        'id' => (string) $clientId,
        'email' => (string) $row['email'],
        'ip' => $ip,
        'when' => $stamp,
        'contact' => $contact
    ));
    $savedAt = $when->format('Y-m-d H:i:s');
    $update = $conn->prepare('UPDATE client_users SET login_notice_at = ?, login_notice_ip = ? WHERE id = ?');
    if ($update) {
        $update->bind_param('ssi', $savedAt, $ip, $clientId);
        $update->execute();
        $update->close();
    }
}

function billing_mail_named_event($conn, $email, $name, $key, $map = array())
{
    $catalog = billing_mail_catalog();
    if (!isset($catalog[$key]) || !billing_mail_ok($email)) {
        return array('ok' => false, 'error' => 'That email could not be addressed.');
    }
    $item = $catalog[$key];
    $lines = array();
    foreach ($item['lines'] as $line) {
        $filled = trim(billing_mail_fill($line, $map));
        if ($filled !== '') {
            $lines[] = $filled;
        }
    }
    $who = trim((string) $name) !== '' ? trim((string) $name) : 'there';
    $body = array('Hello ' . $who . ',', '');
    foreach ($lines as $line) {
        $body[] = $line;
    }
    $body[] = '';
    $body[] = site_sender_name();
    return billing_mail_person($conn, $email, billing_mail_fill($item['subject'], $map), $body);
}

function billing_notify_test($conn)
{
    $to = billing_notify_address($conn);
    return billing_notify_send($conn, 'Test: request emails are reaching this inbox', array(
        'This is a test from the Aakash Technologies admin panel.',
        'If this message is in the inbox, new requests can be sent to ' . $to . '.',
        'Requests covered: contact messages, service orders, domain requests, paid domains, wallet top-ups, identity checks, and support tickets.',
        'Sent at ' . date('Y-m-d H:i') . '.'
    ), true);
}
