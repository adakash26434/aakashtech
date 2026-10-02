<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/domain-check.php';
require_once __DIR__ . '/includes/seo.php';

$publicSite = site_public_settings($conn);
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$sitePhone = $publicSite['site_phone'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$loggedIn = is_client_logged_in();
$tld = isset($_GET['tld']) ? domain_tld($_GET['tld']) : (isset($_POST['tld']) ? domain_tld($_POST['tld']) : 'com.np');
$error = '';
$offer = isset($_SESSION['domain_offer']) && is_array($_SESSION['domain_offer']) ? $_SESSION['domain_offer'] : null;
if ($offer && (!isset($offer['at']) || (time() - (int) $offer['at']) > 1200)) {
    $offer = null;
    unset($_SESSION['domain_offer']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_domain'])) {
    verify_csrf();
    $last = isset($_SESSION['domain_check_at']) ? (int) $_SESSION['domain_check_at'] : 0;
    if ((time() - $last) < 3) {
        $error = 'Wait a moment, then check the name again.';
    } else {
        $_SESSION['domain_check_at'] = time();
        $tld = domain_tld(isset($_POST['tld']) ? $_POST['tld'] : '');
        $result = domain_check_result(isset($_POST['label']) ? $_POST['label'] : '', $tld);
        if ($result['status'] === 'invalid') {
            $error = 'Enter the name only, such as yourcoop. Leave out www and the ending.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        } elseif ($result['status'] === 'unknown') {
            $error = 'The registry could not be checked just now. Try the name again in a moment.';
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
            header('Location: domain.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_domain'])) {
    verify_csrf();
    $offer = isset($_SESSION['domain_offer']) && is_array($_SESSION['domain_offer']) ? $_SESSION['domain_offer'] : null;
    $holderKind = isset($_POST['holder_kind']) && $_POST['holder_kind'] === 'organization' ? 'organization' : 'individual';
    $holderName = billing_plain_line(isset($_POST['holder_name']) ? $_POST['holder_name'] : '', 200);
    if (!$offer || $offer['status'] !== 'available' || (time() - (int) $offer['at']) > 1200) {
        $error = 'Check the name again before sending the request.';
        $offer = null;
    } elseif (strlen($holderName) < 2) {
        $error = 'Enter the person or organization the name is for.';
    } elseif (domain_request_open($conn, $offer['domain'])) {
        $error = 'That name already has a request.';
    } else {
        $again = domain_check_result($offer['label'], $offer['tld']);
        if ($again['status'] !== 'available' || $again['domain'] !== $offer['domain']) {
            $error = 'That name is no longer free. Check it again.';
            unset($_SESSION['domain_offer']);
            $offer = null;
        }
    }
    $clientId = $loggedIn ? (int) get_client_id() : 0;
    if ($error === '' && !$loggedIn) {
        $accountName = billing_plain_line(isset($_POST['account_name']) ? $_POST['account_name'] : '', 80);
        $email = strtolower(trim((string) (isset($_POST['email']) ? $_POST['email'] : '')));
        $phone = auth_mobile_number(isset($_POST['phone']) ? $_POST['phone'] : '');
        $password = (string) (isset($_POST['password']) ? $_POST['password'] : '');
        if (strlen($accountName) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter the account name and a valid email.';
        } elseif ($phone === '') {
            $error = 'Enter a 10-digit mobile number.';
        } elseif (strlen($password) < 6) {
            $error = 'Use a password of at least 6 characters.';
        } else {
            $exists = $conn->prepare('SELECT id FROM client_users WHERE email = ? LIMIT 1');
            $exists->bind_param('s', $email);
            $exists->execute();
            $found = db_fetch_assoc($exists);
            $exists->close();
            if ($found) {
                $error = 'That email already has an account. Sign in, then send the domain request.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $color = '#0b8b7a';
                $company = $holderKind === 'organization' ? $holderName : '';
                $insert = $conn->prepare('INSERT INTO client_users (name, email, password, phone, company, avatar_color) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->bind_param('ssssss', $accountName, $email, $hash, $phone, $company, $color);
                $insert->execute();
                $clientId = (int) $conn->insert_id;
                $insert->close();
                if ($clientId < 1) {
                    $error = 'The account could not be created.';
                } else {
                    $_SESSION['client_id'] = $clientId;
                    $_SESSION['client_name'] = $accountName;
                    $_SESSION['client_email'] = $email;
                    $loggedIn = true;
                }
            }
        }
    }
    $document = '';
    if ($error === '' && $offer && $offer['tld'] === 'com.np') {
        $stored = domain_store_document($clientId, isset($_FILES['document']) ? $_FILES['document'] : array());
        if (!$stored['ok']) {
            $error = $stored['error'];
        } else {
            $document = $stored['path'];
        }
    }
    if ($error === '' && $offer) {
        $status = 'requested';
        $stmt = $conn->prepare('INSERT INTO domain_requests (client_id, domain_name, tld, holder_kind, holder_name, document_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssss', $clientId, $offer['domain'], $offer['tld'], $holderKind, $holderName, $document, $status);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['domain_offer']);
        auth_fresh_session();
        header('Location: client/domains.php');
        exit;
    }
}

$prices = array('com' => '', 'com.np' => '');
foreach (array('com' => 'domain-com', 'com.np' => 'domain-np') as $priceTld => $planCode) {
    $plan = billing_find_plan($conn, $planCode);
    if ($plan && (float) $plan['price'] > 0) {
        $bill = billing_vat_bill(billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0));
        $prices[$priceTld] = billing_money_label($bill['total']) . ' with 13% VAT for one year';
    }
}
$checkedLabel = $offer ? $offer['label'] : '';
$checkedTld = $offer ? $offer['tld'] : $tld;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain registration | <?= site_escape($siteName) ?></title>
    <meta name="robots" content="index, follow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script defer src="assets/js/site.js"></script>
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/includes/site-notice.php'; ?>
    <?php include __DIR__ . '/includes/site-header.php'; ?>
    <main id="main-content">
        <section class="section detail-section">
            <div class="wrap domain-layout">
                <div>
                    <p class="section-kicker">Domain registration</p>
                    <h1 class="font-heading">Check the name, then request it.</h1>
                    <p class="domain-lead">.com.np is checked on Nepal's official register at register.com.np. .com is checked in the Verisign .com registry record. A free name can be requested. The team registers it separately, and the client portal shows Active after that is done.</p>
                </div>
                <div class="domain-panel">
                    <?php if ($error !== ''): ?>
                        <p class="form-status form-status--error" role="alert"><?= site_escape($error) ?></p>
                    <?php endif; ?>
                    <form method="POST" class="domain-check">
                        <input type="hidden" name="csrf_token" value="<?= site_escape(csrf_token()) ?>">
                        <label for="label">Name</label>
                        <input id="label" name="label" type="text" maxlength="63" required placeholder="yourcoop" value="<?= site_escape($checkedLabel) ?>">
                        <div class="domain-tlds">
                            <label><input type="radio" name="tld" value="com.np" <?= $checkedTld === 'com.np' ? 'checked' : '' ?>> .com.np</label>
                            <label><input type="radio" name="tld" value="com" <?= $checkedTld === 'com' ? 'checked' : '' ?>> .com</label>
                        </div>
                        <button class="button button--primary" type="submit" name="check_domain" value="1">Check availability</button>
                    </form>
                    <?php if ($offer && $offer['status'] === 'taken'): ?>
                        <p class="domain-result domain-result--taken"><?= site_escape($offer['domain']) ?> is already registered. Choose another name.</p>
                    <?php elseif ($offer && $offer['status'] === 'available'): ?>
                        <p class="domain-result domain-result--free"><?= site_escape($offer['domain']) ?> is available. Send the request below.<?php if ($prices[$offer['tld']] !== ''): ?> Yearly bill <?= site_escape($prices[$offer['tld']]) ?>.<?php endif; ?></p>
                        <form method="POST" enctype="multipart/form-data" class="domain-request">
                            <input type="hidden" name="csrf_token" value="<?= site_escape(csrf_token()) ?>">
                            <label for="holder_name"><?= $offer['tld'] === 'com.np' ? 'Person or organization on the document' : 'Person or organization' ?></label>
                            <input id="holder_name" name="holder_name" type="text" maxlength="200" required>
                            <div class="domain-tlds">
                                <label><input type="radio" name="holder_kind" value="individual" checked> Individual</label>
                                <label><input type="radio" name="holder_kind" value="organization"> Organization</label>
                            </div>
                            <?php if ($offer['tld'] === 'com.np'): ?>
                                <label for="document">Registry document</label>
                                <p class="domain-note">An individual attaches citizenship, a passport, a driving licence, a voter card, an NRN card, or a Nepal resident visa. An organization attaches its registration certificate. register.com.np accepts a JPG or PNG.</p>
                                <input id="document" name="document" type="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                            <?php endif; ?>
                            <?php if (!$loggedIn): ?>
                                <h2 class="font-heading">Create the client account</h2>
                                <label for="account_name">Your name</label>
                                <input id="account_name" name="account_name" type="text" maxlength="80" required autocomplete="name">
                                <label for="email">Email</label>
                                <input id="email" name="email" type="email" maxlength="254" required autocomplete="email">
                                <label for="phone">Mobile</label>
                                <input id="phone" name="phone" type="tel" maxlength="16" required inputmode="tel" autocomplete="tel" placeholder="98XXXXXXXX">
                                <label for="password">Password</label>
                                <input id="password" name="password" type="password" minlength="6" required autocomplete="new-password">
                            <?php else: ?>
                                <p class="domain-note">This request is saved to the account you are signed in with.</p>
                            <?php endif; ?>
                            <button class="button button--primary" type="submit" name="request_domain" value="1">Send registration request</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
