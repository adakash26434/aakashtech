<?php
/** Support: wording and small rules shared by the ticket page and its tests. No output. */

function support_topics()
{
    return array('sms' => 'SMS or voice', 'wallet' => 'Wallet or payment', 'domain' => 'Domain', 'hosting' => 'Hosting or email', 'website' => 'Website or training',
        'identity' => 'Identity verification', 'account' => 'My account or login', 'other' => 'Something else');
}

/** Urgency in words a person can choose without guessing what "medium" means. */
function support_urgency()
{
    return array(
        'low' => array('A question, no hurry', 'We answer within a few working days.'),
        'medium' => array('Something is not right, I can wait a day', 'The usual choice.'),
        'high' => array('My work is blocked', 'We look at it first.'),
        'urgent' => array('Money or messages are affected right now', 'Use this only if it cannot wait.')
    );
}

function support_status_words($status)
{
    $map = array(
        'open' => array('Waiting for us', 'warn', 'We have it and will answer soon.'),
        'in_progress' => array('We are working on it', 'info', 'The team is on it. Add anything new below.'),
        'resolved' => array('Solved', 'ok', 'Tell us below if it is not fixed. That opens it again.'),
        'closed' => array('Closed', 'none', 'This ticket is finished.')
    );
    return isset($map[$status]) ? $map[$status] : array(ucfirst(str_replace('_', ' ', (string) $status)), 'none', '');
}

function support_group($status)
{
    return in_array($status, array('open', 'in_progress'), true) ? 'open' : 'done';
}

/** The text saved on the ticket: the person's words, with the topic and service on top so nobody has to ask. */
function support_compose_description($topicKey, $serviceLabel, $text)
{
    $topics = support_topics();
    $lines = array();
    if (isset($topics[$topicKey])) {
        $lines[] = 'About: ' . $topics[$topicKey];
    }
    if ($serviceLabel !== '') {
        $lines[] = 'Service: ' . $serviceLabel;
    }
    $text = trim((string) $text);
    return $lines ? implode("\n", $lines) . "\n\n" . $text : $text;
}

/** Quick answers shown before a ticket is opened: each is a place the client can look in under a minute. */
function support_quick_answers()
{
    return array(
        array('My SMS were not delivered', 'See what happened to every message, and why.', 'sms-report.php'),
        array('My top-up is not in the wallet', 'It shows as "Waiting for confirmation" until we confirm the payment.', 'wallet.php?type=waiting'),
        array('When does my service renew?', 'Renewal dates and the wallet cover are on My Services.', 'services.php'),
        array('Is my identity approved?', 'Your status and any note from us are on the identity page.', 'kyc.php')
    );
}
