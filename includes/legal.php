<?php

function site_privacy_builtin($name, $email, $location)
{
    $name = trim((string) $name) !== '' ? trim((string) $name) : 'Aakash Technologies';
    $email = trim((string) $email) !== '' ? trim((string) $email) : 'info@aakashtechnologies.com.np';
    $place = trim((string) $location) !== '' ? trim((string) $location) : 'Nepal';
    return $name . ' keeps this notice so a person can see what personal information is collected, why it is collected, and how to ask about it. It follows the Individual Privacy Act, 2075 (2018) and the Individual Privacy Regulation, 2077 (2020). Those laws treat a name, an address, a telephone number, an email address, and an identity document as personal information. They require consent before that information is collected, and they require the purpose to be stated. Nepal does not yet have a separate statute that names website cookies. This notice describes what this site actually does.'
        . "\n\n" . 'Who holds it. ' . $name . ' holds the information, from ' . $place . '. A question or a correction request goes to ' . $email . ', or to a support ticket after sign-in.'
        . "\n\n" . 'What is collected. An account needs a name, an email address, and a 10-digit mobile number. A company and an address are optional. The password is stored only as a hash. The sign-in email and the mobile number stay fixed unless the team changes them after a request. A Google Authenticator secret is stored so the next sign-in can be checked.'
        . "\n\n" . 'A service order, a wallet payment reference, a domain holder name and address, and a domain document are stored with that order. A card number is not stored. SMS and voice stay closed until identity is approved. An individual submits a citizenship certificate or a National Identity Card. An organization submits its registration certificate, a PAN or VAT certificate, and the authorized person\'s identity document, together with the purpose of the messages. A message the client asks the site to send, and the numbers it is sent to, are stored so the team can check what went out. A support ticket stores the subject and the message. The public contact form stores the name, the email, and the message. A mobile number is not required on that form. The host may keep a connection log, including the network address, to protect the site.'
        . "\n\n" . 'Why it is used. The details are used to open the account, take a payment, deliver the service that was bought or booked, and write to that person about it. A finished sign-in also sends a notice to the account email. That notice includes the account number, the email, the network address, and the Nepal time. It is not sent as an SMS, so no SMS credit is used. Identity details are used only to decide whether SMS and voice can be opened. A contact message is used only to reply. The information is not sold, and it is not used for a purpose that is not stated on this page.'
        . "\n\n" . 'Consent. Creating an account, sending the contact form, submitting identity, or paying for a service is the consent to use those details for the purpose on this page. Writing to ' . $email . ', or opening a support ticket, is how a person asks what is held or asks for a correction. An approved identity record is not edited from the portal. The team handles that change.'
        . "\n\n" . 'Who else sees it. The team that runs the account sees it. If the public assistant is switched on, a question typed on the public pages is sent to that assistant so it can answer from those pages. Passwords, wallet balances, and identity files are not given to the assistant. Typefaces, icons, and a small interface script are loaded from outside services so the pages can be drawn. Those services can see that a browser requested the file. This site does not add an advertising network.'
        . "\n\n" . 'How long it is kept. Account and billing records stay while the account is open and while a paid service, a domain year, or a dispute still needs them. A person can ask for the account to be closed. A record that a payment or an identity check already happened may be kept for that history.'
        . "\n\n" . 'Security. The password is hashed. A hosting password saved for the client is sealed. The sign-in cookie cannot be read by page scripts. The cookie notice explains that cookie.'
        . "\n\n" . 'Changes. The team can edit this notice. The copy on this page is the current one.';
}

function site_cookie_builtin($name)
{
    $name = trim((string) $name) !== '' ? trim((string) $name) : 'Aakash Technologies';
    return $name . ' sets one cookie of its own. It keeps a person signed in, and it protects a form from being submitted by another site. The cookie is usually named PHPSESSID. It lasts until the browser is closed. Scripts on the page cannot read it. When the site is opened over https, the cookie is sent only over that secure connection. It is not used to advertise, and it is not sold.'
        . "\n\n" . 'The Individual Privacy Act, 2075 (2018) does not name cookies. A cookie that only keeps the account signed in is part of providing the account. It does not build a marketing profile. This site does not set analytics cookies or advertising cookies.'
        . "\n\n" . 'The pages load typefaces, icons, and an interface script from outside hosts so the pages can be drawn. Those hosts can see the request. They are not given the account password or the identity files.'
        . "\n\n" . 'A person can block cookies in the browser. The public pages still open. Sign-in and a protected form need this cookie, so those steps stop if it is blocked.'
        . "\n\n" . 'The team can edit this notice. The copy on this page is the current one.';
}

function site_terms_builtin($name, $email)
{
    $name = trim((string) $name) !== '' ? trim((string) $name) : 'Aakash Technologies';
    $email = trim((string) $email) !== '' ? trim((string) $email) : 'info@aakashtechnologies.com.np';
    return 'These terms apply when a person or an organization creates an account with ' . $name . ', adds wallet funds, or buys or books a service on this site.'
        . "\n\n" . 'The account. One account belongs to one person or one organization. The details on it must be true. The account holder keeps the password and the Google Authenticator code private and is responsible for what is done after a sign-in with them.'
        . "\n\n" . 'Rates and the wallet. Rates are shown in Nepali rupees on the service pages, with 13% VAT where it applies. Services are paid from the prepaid wallet. A top-up shows in the wallet after the team confirms the payment. Wallet funds are used only for services on this site.'
        . "\n\n" . 'Renewals. A plan with auto-renew turned on renews from the wallet on its renewal date. If the wallet is short, the renewal is tried again for 7 days. After that the service pauses until the wallet covers it. Auto-renew can be turned off on My Services.'
        . "\n\n" . 'SMS and voice. SMS and voice calls open after identity (KYC) is approved. A sender name is used only after the team approves it. Before sending, the client accepts the declaration that the service will not be used for anything Nepal law forbids, or for false or fraudulent messages. Messages go only to people who expect them. A credit is used when a message or call is sent. The team can stop sending from an account that breaks these rules.'
        . "\n\n" . 'Domains. A domain is registered for a year at a time, in the name and with the documents the client gives. The team registers it after the yearly bill is paid. If the registry refuses the name, the amount can be returned to the wallet.'
        . "\n\n" . 'Hosting, email, and websites. The client is responsible for what is published or stored on the account. The team can pause a service that is used for unlawful content, spam, or an attack on another system.'
        . "\n\n" . 'Support. Questions go through a support ticket after sign-in, or to ' . $email . '.'
        . "\n\n" . 'Changes. The team can edit these terms. The copy on this page is the current one.';
}

function site_legal_text($settings, $key)
{
    $stored = (is_array($settings) && isset($settings[$key])) ? trim((string) $settings[$key]) : '';
    if ($stored !== '') {
        return $stored;
    }
    $name = (is_array($settings) && isset($settings['site_name'])) ? $settings['site_name'] : 'Aakash Technologies';
    $email = (is_array($settings) && isset($settings['site_email'])) ? $settings['site_email'] : 'info@aakashtechnologies.com.np';
    $location = (is_array($settings) && isset($settings['site_location'])) ? $settings['site_location'] : 'Nepal';
    if ($key === 'cookie_policy') {
        return site_cookie_builtin($name);
    }
    if ($key === 'terms_of_service') {
        return site_terms_builtin($name, $email);
    }
    return site_privacy_builtin($name, $email, $location);
}

function site_legal_plain($value, $max)
{
    $value = str_replace(array("\r\n", "\r"), "\n", (string) $value);
    $value = preg_replace("/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/", '', $value);
    if (!is_string($value)) {
        return '';
    }
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return trim($value);
}
