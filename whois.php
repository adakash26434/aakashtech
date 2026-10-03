<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/domain-check.php';

$publicSite = site_public_settings($conn);
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$error = '';
$typedName = '';
$typedTld = isset($_GET['tld']) ? domain_tld($_GET['tld']) : '';
$record = isset($_SESSION['whois_record']) && is_array($_SESSION['whois_record']) ? $_SESSION['whois_record'] : null;
if ($record && (!isset($record['at']) || (time() - (int) $record['at']) > 600)) {
    $record = null;
    unset($_SESSION['whois_record']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_whois'])) {
    verify_csrf();
    $mathError = auth_math_verify('whois-check', isset($_POST['human_check']) ? $_POST['human_check'] : '');
    $last = isset($_SESSION['whois_check_at']) ? (int) $_SESSION['whois_check_at'] : 0;
    $typedName = trim((string) (isset($_POST['whois_domain']) ? $_POST['whois_domain'] : ''));
    $typedTld = domain_tld(isset($_POST['tld']) ? $_POST['tld'] : '');
    if ($mathError !== '') {
        $error = $mathError;
    } elseif ((time() - $last) < 3) {
        $error = 'Wait a moment, then check the name again.';
    } else {
        $_SESSION['whois_check_at'] = time();
        $result = domain_whois_lookup($typedName, $typedTld);
        if ($result['status'] === 'invalid') {
            $error = 'Enter a Nepal name or a .com name, such as yourcoop.com.np.';
            unset($_SESSION['whois_record']);
            $record = null;
        } elseif ($result['status'] === 'unknown') {
            $error = 'The record could not be read just now. Try the name again in a moment.';
            unset($_SESSION['whois_record']);
            $record = null;
        } else {
            $result['at'] = time();
            $_SESSION['whois_record'] = $result;
            auth_math_clear('whois-check');
            header('Location: whois.php');
            exit;
        }
    }
}

if ($record) {
    $typedName = isset($record['domain']) ? (string) $record['domain'] : $typedName;
    $typedTld = isset($record['tld']) ? (string) $record['tld'] : $typedTld;
} elseif ($typedName === '' && isset($_GET['name'])) {
    $asked = domain_whois_split($_GET['name']);
    if ($asked) {
        $typedName = $asked['domain'];
        $typedTld = $asked['tld'];
    }
}
$registerHref = 'domain.php';
if ($record && isset($record['domain'], $record['tld']) && $record['status'] === 'free') {
    $parts = domain_whois_split($record['domain']);
    if ($parts) {
        $registerHref = 'domain.php?label=' . rawurlencode($parts['label']) . '&tld=' . rawurlencode($parts['tld']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once __DIR__ . '/includes/seo.php';
    site_seo_print('WHOIS check up | ' . $siteName, 'See the public record for a Nepal domain name or a .com name.', 'whois.php', array(), $siteLogo);
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <script defer src="https://unpkg.com/lucide@0.383.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="assets/js/site.js"></script>
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/includes/site-notice.php'; ?>
    <?php include __DIR__ . '/includes/site-header.php'; ?>
    <main id="main-content">
        <section class="section detail-section">
            <div class="wrap">
                <div class="whois-top">
                <div>
                    <p class="section-kicker">WHOIS check up</p>
                    <h1 class="font-heading">See who holds the name.</h1>
                    <p class="domain-lead">Enter a Nepal name, such as .com.np or .coop.np, or a .com name. The public record is shown in a table below. A name with no record can be requested under <a href="domain.php">Domain registration</a>.</p>
                </div>
                <div class="domain-panel">
                    <?php if ($error !== ''): ?>
                        <p class="form-status form-status--error" role="alert"><?= site_escape($error) ?></p>
                    <?php endif; ?>
                    <form method="POST" class="domain-check" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= site_escape(csrf_token()) ?>">
                        <label for="whois-domain">Domain name</label>
                        <input id="whois-domain" name="whois_domain" type="text" maxlength="80" required placeholder="aakashdigital.com.np" value="<?= site_escape($typedName) ?>" autocomplete="off" autocapitalize="off" spellcheck="false">
                        <label for="tld">Ending, if it is not already in the name</label>
                        <select id="tld" name="tld" class="domain-ending">
                            <option value="" <?= $typedTld === '' ? 'selected' : '' ?>>Already included</option>
                            <?php foreach (domain_np_tlds() as $ending): ?>
                                <option value="<?= site_escape($ending) ?>" <?= $typedTld === $ending ? 'selected' : '' ?>>.<?= site_escape($ending) ?></option>
                            <?php endforeach; ?>
                            <option value="com" <?= $typedTld === 'com' ? 'selected' : '' ?>>.com</option>
                        </select>
                        <label for="human_check">What is <?= site_escape(auth_math_prompt('whois-check')) ?>?</label>
                        <input id="human_check" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" placeholder="Answer">
                        <button class="button button--primary" type="submit" name="check_whois" value="1">Check the record</button>
                    </form>
                </div>
                </div>
                <?php if ($record && $record['status'] === 'free'): ?>
                    <div class="whois-sheet">
                        <p class="domain-result domain-result--free"><?= site_escape($record['domain']) ?> has no public record. It can be requested.</p>
                        <a class="button button--primary" href="<?= site_escape($registerHref) ?>">Request this name</a>
                    </div>
                <?php elseif ($record && $record['status'] === 'registered'): ?>
                    <?php
                    $whoisGroups = array('domain' => array(), 'registrar' => array());
                    foreach ($record['rows'] as $row) {
                        $group = (isset($row['group']) && $row['group'] === 'registrar') ? 'registrar' : 'domain';
                        $whoisGroups[$group][] = $row;
                    }
                    $whoisTitles = array('domain' => 'Domain information', 'registrar' => 'Registrar information');
                    ?>
                    <div class="whois-sheet">
                        <p class="domain-result domain-result--taken"><?= site_escape($record['domain']) ?> is already registered.</p>
                        <?php foreach ($whoisGroups as $group => $groupRows): ?>
                            <?php if (!$groupRows) { continue; } ?>
                            <h2 class="whois-heading font-heading"><?= site_escape($whoisTitles[$group]) ?></h2>
                            <table class="whois-table">
                                <tbody>
                                    <?php foreach ($groupRows as $row): ?>
                                        <tr>
                                            <th scope="row"><?= site_escape($row['label']) ?></th>
                                            <td><?= nl2br(site_escape($row['value'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
