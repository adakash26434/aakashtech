<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/domain-check.php';
require_once __DIR__ . '/includes/seo.php';

$publicSite = site_public_settings($conn);
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$loggedIn = is_client_logged_in();
$freshClient = false;
$tld = isset($_GET['tld']) ? domain_tld($_GET['tld']) : (isset($_POST['tld']) ? domain_tld($_POST['tld']) : '');
$error = '';
$typedLabel = '';
$offer = isset($_SESSION['domain_offer']) && is_array($_SESSION['domain_offer']) ? $_SESSION['domain_offer'] : null;
if ($offer && (!isset($offer['at']) || (time() - (int) $offer['at']) > 1200)) {
    $offer = null;
    unset($_SESSION['domain_offer']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_domain'])) {
    verify_csrf();
    $mathError = auth_math_verify('domain-check', isset($_POST['human_check']) ? $_POST['human_check'] : '');
    $last = isset($_SESSION['domain_check_at']) ? (int) $_SESSION['domain_check_at'] : 0;
    if ($mathError !== '') {
        $error = $mathError;
        $typedLabel = trim((string) (isset($_POST['label']) ? $_POST['label'] : ''));
    } elseif ((time() - $last) < 3) {
        $error = 'Wait a moment, then check the name again.';
    } else {
        $_SESSION['domain_check_at'] = time();
        $typedLabel = trim((string) (isset($_POST['label']) ? $_POST['label'] : ''));
        $tld = domain_tld(isset($_POST['tld']) ? $_POST['tld'] : '');
        $result = $tld === '' ? array('status' => 'invalid') : domain_check_result(isset($_POST['label']) ? $_POST['label'] : '', $tld);
        if ($tld === '') {
            $error = 'Choose an ending, such as .com.np, .coop.np, or .com.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        } elseif ($result['status'] === 'invalid') {
            $error = 'Enter the name only, such as yourcoop. Leave out www and the ending.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        } elseif ($result['status'] === 'unknown') {
            $error = 'The name could not be checked just now. Try the name again in a moment.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        } else {
            $_SESSION['domain_offer'] = array(
                'domain' => $result['domain'],
                'tld' => $result['tld'],
                'label' => $result['label'],
                'status' => $result['status'],
                'at' => time()
            );
            auth_math_clear('domain-check');
            header('Location: domain.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_domain'])) {
    verify_csrf();
    $mathError = auth_math_verify('domain-request', isset($_POST['human_check']) ? $_POST['human_check'] : '');
    $offer = isset($_SESSION['domain_offer']) && is_array($_SESSION['domain_offer']) ? $_SESSION['domain_offer'] : null;
    $holderKind = isset($_POST['holder_kind']) && $_POST['holder_kind'] === 'organization' ? 'organization' : 'individual';
    $holderName = billing_plain_line(isset($_POST['holder_name']) ? $_POST['holder_name'] : '', 200);
    $holderAddress = billing_plain_line(isset($_POST['holder_address']) ? $_POST['holder_address'] : '', 200);
    $accountName = billing_plain_line(isset($_POST['account_name']) ? $_POST['account_name'] : '', 80);
    $accountEmail = strtolower(trim((string) (isset($_POST['email']) ? $_POST['email'] : '')));
    $accountPhone = trim((string) (isset($_POST['phone']) ? $_POST['phone'] : ''));
    if ($mathError !== '') {
        $error = $mathError;
    } elseif (!$offer || $offer['status'] !== 'available' || (time() - (int) $offer['at']) > 1200) {
        $error = 'Check the name again before sending the request.';
        $offer = null;
    } elseif (strlen($holderName) < 2) {
        $error = 'Enter the person or organization the name is for.';
    } elseif (strlen($holderAddress) < 8) {
        $error = 'Enter the address for the registration.';
    } elseif (domain_request_open($conn, $offer['domain'])) {
        $error = 'That name already has a request.';
    } else {
        $again = domain_check_result($offer['label'], $offer['tld']);
        if ($again['status'] === 'unknown') {
            $error = 'The name could not be checked just now. Try again in a moment.';
        } elseif ($again['status'] !== 'available' || $again['domain'] !== $offer['domain']) {
            $error = 'That name is no longer free. Check it again.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        }
    }
    $clientId = $loggedIn ? (int) get_client_id() : 0;
    if ($error === '' && !$loggedIn) {
        $email = $accountEmail;
        $phone = auth_mobile_number($accountPhone);
        $password = (string) (isset($_POST['password']) ? $_POST['password'] : '');
        if (strlen($accountName) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter the account name and a valid email.';
        } elseif ($phone === '') {
            $error = 'Enter a 10-digit mobile number.';
        } elseif (strlen($password) < 8) {
            $error = 'Use a password of at least 8 characters.';
        } else {
            $company = $holderKind === 'organization' ? $holderName : '';
            $taken = billing_client_taken($conn, $email, $phone, $company, 0);
            if ($taken !== '') {
                $error = $taken . ' Sign in, then send the domain request.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $color = '#0b8b7a';
                $insert = $conn->prepare('INSERT INTO client_users (name, email, password, phone, company, avatar_color) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->bind_param('ssssss', $accountName, $email, $hash, $phone, $company, $color);
                $insert->execute();
                $clientId = (int) $conn->insert_id;
                $insert->close();
                if ($clientId < 1) {
                    $error = 'The account could not be created.';
                } else {
                    billing_mail_client_event($conn, $clientId, 'account');
                    $freshClient = true;
                }
            }
        }
    }
    $document = '';
    if ($error === '' && $offer && domain_is_np($offer['tld'])) {
        $stored = domain_store_document($clientId, isset($_FILES['document']) ? $_FILES['document'] : array());
        if (!$stored['ok']) {
            $error = $stored['error'];
        } else {
            $document = $stored['path'];
        }
    }
    $bill = ($error === '' && $offer) ? domain_year_bill($conn, $offer['tld']) : array('total' => '0.00');
    if ($error === '' && $offer && (float) $bill['total'] <= 0) {
        $error = 'The yearly domain price is not set yet.';
    }
    if ($error === '' && $offer && $clientId < 1) {
        $error = 'Sign in, then send the domain request.';
    }
    if ($error === '' && $offer) {
        $status = 'requested';
        $price = $bill['total'];
        $stmt = $conn->prepare('INSERT INTO domain_requests (client_id, domain_name, tld, holder_kind, holder_name, holder_address, document_path, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssssss', $clientId, $offer['domain'], $offer['tld'], $holderKind, $holderName, $holderAddress, $document, $price, $status);
        $stmt->execute();
        $requestId = (int) $conn->insert_id;
        $stmt->close();
        if ($requestId < 1) {
            if ($document !== '') {
                $savedFile = domain_safe_file($clientId, $document);
                if ($savedFile !== '') {
                    @unlink($savedFile);
                }
            }
            $error = 'The request could not be saved.';
        } elseif (!domain_keep_first_request($conn, $offer['domain'], $requestId)) {
            if ($document !== '') {
                $savedFile = domain_safe_file($clientId, $document);
                if ($savedFile !== '') {
                    @unlink($savedFile);
                }
            }
            $error = 'That name already has a request.';
        } else {
            unset($_SESSION['domain_offer']);
            auth_math_clear('domain-request');
            billing_notify($conn, 'Domain request: ' . $offer['domain'], array(
                'A domain registration request was saved.',
                'Domain: ' . $offer['domain'],
                'Holder: ' . $holderName,
                'Address: ' . $holderAddress,
                'Yearly bill: NPR ' . $price,
                'Payment: still waiting',
                'Client: ' . billing_notify_client_label($conn, $clientId),
                'Open Admin → Domains.'
            ));
            billing_mail_client_event($conn, $clientId, 'domain-request', array(
                'domain' => $offer['domain'],
                'amount' => $price
            ));
            auth_fresh_session();
            $covered = billing_balance($conn, $clientId);
            $short = (float) $price - $covered;
            $next = $short > 0.5 ? 'wallet.php?amount=' . (int) ceil($short) . '&for=domain' : 'domains.php';
            if ($freshClient) {
                totp_open_gate($conn, 'client', array(
                    'id' => $clientId,
                    'name' => $accountName,
                    'email' => $email
                ), $next);
                header('Location: client/two-factor.php');
                exit;
            }
            header('Location: client/' . $next);
            exit;
        }
    }
    if ($freshClient && $error !== '') {
        $error .= ' The account was created. Sign in, then send the request again.';
    }
}

$yearBill = $offer ? domain_year_bill($conn, $offer['tld']) : array('label' => '');
if ($typedLabel === '' && !$offer && isset($_GET['label'])) {
    $fromGet = domain_label($_GET['label']);
    if ($fromGet !== '') {
        $typedLabel = $fromGet;
    }
}
$checkedLabel = $offer ? $offer['label'] : $typedLabel;
$checkedTld = $offer ? $offer['tld'] : $tld;
if (!isset($holderName)) {
    $holderName = '';
}
if (!isset($holderKind)) {
    $holderKind = 'individual';
}
if (!isset($holderAddress)) {
    $holderAddress = '';
}
if (!isset($accountName)) {
    $accountName = '';
}
if (!isset($accountEmail)) {
    $accountEmail = '';
}
if (!isset($accountPhone)) {
    $accountPhone = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php site_seo_print('Domain registration in Nepal | ' . $siteName, 'Register a .com.np, .np, or .com domain name. Check the name, pay the yearly bill from the wallet, and the team registers it.', 'domain.php', array(), $siteLogo); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <link rel="stylesheet" href="assets/css/ui-shared.css">
    <script defer src="https://unpkg.com/lucide@0.383.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="assets/js/site.js"></script>
    <script defer src="assets/js/forms.js"></script>
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/includes/site-notice.php'; ?>
    <?php include __DIR__ . '/includes/site-header.php'; ?>
    <main id="main-content">
        <section class="section detail-section">
            <div class="wrap domain-page">
            <div class="domain-layout">
                <div>
                    <p class="section-kicker">Domain registration</p>
                    <h1 class="font-heading">Check the name, then request it.</h1>
                    <p class="domain-lead">Choose the ending. Nepal names such as .com.np and .coop.np, and .com, are checked here. To see who already holds a name, use <a href="whois.php">WHOIS check up</a>. A Nepal request includes the document for that name. .edu.np, .gov.np, and .mil.np can be requested, and the team confirms who can hold them. A free name can be requested. You pay the yearly bill from the wallet, the team registers that name, and the client portal shows Active after that. The paid year starts then and renews from the wallet.</p>
                    <h2 class="detail-subhead font-heading">After the name is active</h2>
                    <ul class="detail-points">
                        <li><a href="service.php?slug=hosting-server">Hosting</a> keeps a website online. It is a separate yearly or monthly bill.</li>
                        <li><a href="service.php?slug=professional-email">Domain email</a> opens addresses such as info@the-name. The mailboxes are separate.</li>
                        <li><a href="service.php?slug=custom-websites">A website</a> is booked on its own. The domain does not include the design.</li>
                    </ul>
                </div>
                <div class="domain-panel">
                    <?php if ($error !== ''): ?>
                        <p class="form-status form-status--error" role="alert"><?= site_escape($error) ?></p>
                    <?php endif; ?>
                    <form method="POST" class="domain-check">
                        <input type="hidden" name="csrf_token" value="<?= site_escape(csrf_token()) ?>">
                        <label for="label">Name</label>
                        <input id="label" name="label" type="text" maxlength="63" required placeholder="yourcoop" value="<?= site_escape($checkedLabel) ?>">
                    <label for="tld">Ending</label>
                    <select id="tld" name="tld" class="domain-ending" required>
                        <option value="" <?= $checkedTld === '' ? 'selected' : '' ?>>Choose an ending</option>
                        <?php foreach (domain_np_tlds() as $ending): ?>
                            <option value="<?= site_escape($ending) ?>" <?= $checkedTld === $ending ? 'selected' : '' ?>>.<?= site_escape($ending) ?></option>
                        <?php endforeach; ?>
                        <option value="com" <?= $checkedTld === 'com' ? 'selected' : '' ?>>.com</option>
                    </select>
                        <label for="human_check">What is <?= site_escape(auth_math_prompt('domain-check')) ?>?</label>
                        <input id="human_check" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" placeholder="Answer">
                        <button class="button button--primary" type="submit" name="check_domain" value="1">Check availability</button>
                    </form>
                    <?php if ($offer && $offer['status'] === 'taken'): ?>
                        <p class="domain-result domain-result--taken"><?= site_escape($offer['domain']) ?> is already registered. Choose another name.</p>
                    <?php elseif ($offer && $offer['status'] === 'available'): ?>
                        <p class="domain-result domain-result--free"><?= site_escape($offer['domain']) ?> is available.<?php if ($yearBill['label'] !== ''): ?> Yearly bill <?= site_escape($yearBill['label']) ?>.<?php endif; ?> The request form is below.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($offer && $offer['status'] === 'available'): ?>
                <div class="domain-panel domain-request-sheet">
                    <h2 class="font-heading">Send the request</h2>
                    <form method="POST" enctype="multipart/form-data" class="domain-request">
                        <input type="hidden" name="csrf_token" value="<?= site_escape(csrf_token()) ?>">
                        <div class="domain-fields">
                            <div>
                                <label for="holder_name"><?= domain_is_np($offer['tld']) ? 'Person or organization on the document' : 'Person or organization' ?></label>
                                <input id="holder_name" name="holder_name" type="text" maxlength="200" required value="<?= site_escape($holderName) ?>">
                            </div>
                            <div>
                                <label for="holder_address">Address for the registration</label>
                                <input id="holder_address" name="holder_address" type="text" maxlength="200" required value="<?= site_escape($holderAddress) ?>">
                            </div>
                            <div class="domain-span">
                                <div class="domain-tlds">
                                    <label><input type="radio" name="holder_kind" value="individual" <?= $holderKind !== 'organization' ? 'checked' : '' ?>> Individual</label>
                                    <label><input type="radio" name="holder_kind" value="organization" <?= $holderKind === 'organization' ? 'checked' : '' ?>> Organization</label>
                                </div>
                            </div>
                            <?php if (domain_is_np($offer['tld'])): ?>
                                <div class="domain-span">
                                    <label for="document">Required document</label>
                                    <p class="domain-note">An individual attaches citizenship, a passport, a driving licence, a voter card, an NRN card, or a Nepal resident visa. An organization attaches its registration certificate. Use a JPG or PNG.</p>
                                    <input id="document" name="document" type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                                </div>
                            <?php endif; ?>
                            <?php if (!$loggedIn): ?>
                                <div class="domain-span"><h3 class="font-heading">Create the client account</h3></div>
                                <div>
                                    <label for="account_name">Your name</label>
                                    <input id="account_name" name="account_name" type="text" maxlength="80" required autocomplete="name" value="<?= site_escape($accountName) ?>">
                                </div>
                                <div>
                                    <label for="email">Email</label>
                                    <input id="email" name="email" type="email" maxlength="254" required autocomplete="email" value="<?= site_escape($accountEmail) ?>">
                                </div>
                                <div>
                                    <label for="phone">Mobile</label>
                                    <input id="phone" name="phone" type="tel" maxlength="16" required inputmode="tel" autocomplete="tel" placeholder="10-digit mobile" value="<?= site_escape($accountPhone) ?>">
                                </div>
                                <div>
                                    <label for="password">Password</label>
                                    <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password" placeholder="Min 8 characters">
                                </div>
                            <?php else: ?>
                                <p class="domain-note domain-span">This request is saved to the account you are signed in with.</p>
                            <?php endif; ?>
                            <div>
                                <label for="request_check">What is <?= site_escape(auth_math_prompt('domain-request')) ?>?</label>
                                <input id="request_check" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" placeholder="Answer">
                            </div>
                        </div>
                        <p class="domain-note">Sending the request does not take the payment. If the wallet does not cover this year, the next page asks for that amount. After it is confirmed, pay the bill under My domains.</p>
                        <button class="button button--primary" type="submit" name="request_domain" value="1">Send registration request</button>
                    </form>
                </div>
            <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
