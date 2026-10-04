<?php
require_once __DIR__ . '/includes/session.php';

function site_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function site_posted_value($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function site_render_service_price($pricing, $slug, $compact = false)
{
    if (!isset($pricing[$slug]) || !is_array($pricing[$slug])) {
        return;
    }

    $item = $pricing[$slug];
    $label = trim((string) ($item['label'] ?? ''));
    $amount = trim((string) ($item['amount'] ?? ''));
    $details = trim((string) ($item['details'] ?? ''));
    if ($label === '' && $amount === '' && $details === '') {
        return;
    }

    if ($compact) {
        $shown = $amount;
        if (stripos($shown, 'From ') === 0) {
            $shown = trim(substr($shown, 5));
        }
        if ($shown === '') {
            return;
        }
        $was = trim((string) ($item['was'] ?? ''));
        echo '<p class="service-start">Starts from ';
        if ($was !== '') {
            echo '<s class="rate-was">' . site_escape($was) . '</s> ';
        }
        echo '<strong>' . site_escape($shown) . '</strong>';
        if ($was !== '') {
            echo ' <span class="rate-offer-tag">Offer</span>';
        }
        echo '</p>';
        return;
    }

    $rows = array();
    $notes = array();
    foreach (preg_split('/\r\n|\r|\n/', $details) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $separator = strpos($line, ' — ');
        if ($separator === false) {
            $notes[] = $line;
        } else {
            $rows[] = array(trim(substr($line, 0, $separator)), trim(substr($line, $separator + 5)));
        }
    }

    echo '<div class="service-price">';
    if ($label !== '') {
        echo '<span class="service-price-label">' . site_escape($label) . '</span>';
    }
    if ($amount !== '') {
        echo '<strong class="service-price-value">' . site_escape($amount) . '</strong>';
    }
    if ($rows) {
        echo '<div class="service-price-rows">';
        foreach ($rows as $row) {
            echo '<div class="service-price-row"><span>' . site_escape($row[0]) . '</span><strong>' . site_escape($row[1]) . '</strong></div>';
        }
        echo '</div>';
    }
    if ($notes) {
        echo '<p class="service-price-note">' . nl2br(site_escape(implode("\n", $notes)), false) . '</p>';
    }
    echo '</div>';
}

require_once __DIR__ . '/includes/billing.php';
$publicSite = site_public_defaults();
$serviceCards = billing_public_cards(null);
try {
    require_once __DIR__ . '/config.php';
    $serviceCards = billing_public_cards($conn);
    $publicSite = site_public_settings($conn);
} catch (Throwable $exception) {
    error_log('Homepage service pricing could not be loaded; published defaults are shown.');
}
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$paySentence = 'Add wallet funds by the payment method published for this site. After that payment is confirmed, checkout is immediate.';
if (isset($conn)) {
    $payLabels = array();
    foreach (billing_payment_methods($conn) as $payMethod) {
        $payLabels[] = $payMethod['label'];
    }
    $payCount = count($payLabels);
    if ($payCount === 1) {
        $paySentence = 'Pay by ' . $payLabels[0] . '. After that payment is confirmed, checkout is immediate.';
    } elseif ($payCount === 2) {
        $paySentence = 'Pay by ' . $payLabels[0] . ' or ' . $payLabels[1] . '. After that payment is confirmed, checkout is immediate.';
    } elseif ($payCount > 2) {
        $payLast = array_pop($payLabels);
        $paySentence = 'Pay by ' . implode(', ', $payLabels) . ', or ' . $payLast . '. After that payment is confirmed, checkout is immediate.';
    }
}
$contactServices = array();
foreach (billing_service_definitions() as $serviceDefinition) {
    $contactServices[] = $serviceDefinition['contact'];
}
$contactServices[] = 'Other';
$formValues = array(
    'name' => '',
    'email' => '',
    'phone' => '',
    'service' => '',
    'message' => ''
);
$error = '';
$success = '';

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    foreach ($formValues as $key => $unused) {
        $formValues[$key] = site_posted_value($key);
    }

    $submittedToken = site_posted_value('csrf_token');
    $mathError = auth_math_verify('contact', site_posted_value('human_check'));
    if (!hash_equals($_SESSION['contact_csrf'], $submittedToken)) {
        $error = 'Your form session expired. Please refresh the page and try again.';
    } elseif ($mathError !== '') {
        $error = $mathError;
    } elseif (site_posted_value('website_url') !== '') {
        $success = 'Thanks for reaching out. We will be in touch soon.';
        $formValues = array_fill_keys(array_keys($formValues), '');
    } elseif (
        $formValues['name'] === '' ||
        $formValues['email'] === '' ||
        $formValues['message'] === ''
    ) {
        $error = 'Please complete the required fields.';
    } elseif (
        strlen($formValues['name']) > 140 ||
        strlen($formValues['email']) > 254 ||
        strlen($formValues['phone']) > 40 ||
        strlen($formValues['message']) > 3000
    ) {
        $error = 'One of your answers is too long. Please shorten it and try again.';
    } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($formValues['service'] !== '' && !in_array($formValues['service'], $contactServices, true)) {
        $error = 'Please select one of the listed services.';
    } else {
        try {
            require_once __DIR__ . '/config.php';
            $stmt = $conn->prepare(
                'INSERT INTO inquiries (name, email, phone, service, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $contactPhone = '';
            $stmt->bind_param(
                'sssss',
                $formValues['name'],
                $formValues['email'],
                $contactPhone,
                $formValues['service'],
                $formValues['message']
            );

            if (auth_attempt_blocked($conn, 'contact', 5, 1800)) {
                $error = 'Too many messages from this network. Please wait and try again.';
            } elseif ($stmt->execute()) {
                auth_note_attempt($conn, 'contact');
                auth_math_clear('contact');
                billing_mail_named_event($conn, $formValues['email'], $formValues['name'], 'enquiry');
                billing_notify($conn, 'New enquiry' . ($formValues['service'] !== '' ? ': ' . $formValues['service'] : ''), array(
                    'A visitor sent a message from the website.',
                    'Name: ' . $formValues['name'],
                    'Email: ' . $formValues['email'],
                    'Service: ' . ($formValues['service'] !== '' ? $formValues['service'] : 'Not chosen'),
                    'Message: ' . billing_notify_clip($formValues['message'], 800),
                    'Open Admin → Inquiries.'
                ));
                $success = 'Thanks for reaching out. We will be in touch soon.';
                $formValues = array_fill_keys(array_keys($formValues), '');
                $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
            } else {
                $error = 'We could not send your message just now. Please try again.';
            }
            $stmt->close();
        } catch (Throwable $exception) {
            $error = 'We could not send your message just now. Please try again or email us directly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbfdfc">
    <?php
    require_once __DIR__ . '/includes/seo.php';
    $homeSameAs = array();
    foreach ($siteSocials as $social) {
        $homeSameAs[] = $social['href'];
    }
    site_seo_print(
        'Bulk SMS Provider & Hosting in Nepal | ' . $siteName,
        'Bulk SMS provider, bulk voice calls, and hosting provider in Nepal. Domains, domain email, websites, and cyber training, with the rate on the page.',
        '/',
        site_seo_home_graph($publicSite, $homeSameAs),
        $siteLogo
    );
    ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <link rel="stylesheet" href="assets/css/ui-shared.css">
    <script defer src="assets/vendor/lucide-0.383.0.min.js"></script>
    <script defer src="assets/vendor/alpine-3.14.9.min.js"></script>
    <script defer src="assets/js/site.js"></script>
    <script defer src="assets/js/forms.js"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="site-public font-body antialiased">
    <?php include __DIR__ . '/includes/site-notice.php'; ?>
    <?php include __DIR__ . '/includes/site-header.php'; ?>

    <main id="main-content">
        <section id="home" class="hero">
            <div class="wrap hero-layout">
                <div class="hero-copy">
                    <div class="eyebrow reveal">
                        <span class="eyebrow-dot" aria-hidden="true"></span>
                        <?= site_escape($publicSite['home_eyebrow']) ?>
                    </div>
                    <h1 class="reveal font-heading"><?= site_hero_title_html($publicSite['home_title']) ?></h1>
                    <p class="hero-lede reveal">
                        <?= site_escape($publicSite['home_lede']) ?>
                    </p>
                    <div class="hero-actions reveal">
                        <a class="button button--primary" href="#services">
                            See the rates
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </a>
                        <a class="button button--outline" href="client/shop.php">
                            Buy or book
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="hero-caption reveal">
                        <i data-lucide="map-pin" aria-hidden="true"></i>
                        A local technology partner, based in <?= site_escape($siteLocation) ?>
                    </div>
                </div>

                <div class="hero-art reveal" aria-label="<?= site_escape($siteName) ?> services">
                    <div class="art-orbit" aria-hidden="true"></div>
                    <span class="art-spark art-spark--one" aria-hidden="true"></span>
                    <span class="art-spark art-spark--two" aria-hidden="true"></span>

                    <div class="solution-window">
                        <div class="window-top">
                            <div class="window-brand">
                                <span class="window-brand-mark"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                                <?= site_escape($siteName) ?>
                            </div>
                            <div class="window-dots" aria-hidden="true"><span></span><span></span><span></span></div>
                        </div>
                    <h2 class="window-heading font-heading">Everything is on the page</h2>
                        <p class="window-subtitle">Read the rate, then buy or book. No extra call to explain the service.</p>
                        <div class="solution-list">
                            <a class="solution-row" href="service.php?slug=bulk-sms">
                                <span class="solution-icon"><i data-lucide="message-square-text" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Bulk SMS</strong><span>Type a quantity and see the bill</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </a>
                            <a class="solution-row" href="service.php?slug=bulk-voice">
                                <span class="solution-icon"><i data-lucide="phone-call" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Auto voice calls</strong><span>Nepali or English, same volume rate</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </a>
                            <a class="solution-row" href="#services">
                                <span class="solution-icon"><i data-lucide="server" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Sites, hosting, domain email</strong><span>Domain, site, and mail in one account</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </a>
                            <a class="solution-row" href="service.php?slug=cyber-security">
                                <span class="solution-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Field cyber training</strong><span>The trainer comes to your address</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <div class="art-note">
                        <i data-lucide="circle-check" aria-hidden="true"></i>
                        The bill shows 13% VAT.
                    </div>
                </div>
            </div>
        </section>

        <div class="service-ribbon" aria-label="<?= site_escape($siteName) ?> service categories">
            <div class="wrap service-ribbon-inner">
                <div class="ribbon-lead"><?= site_escape($publicSite['home_ribbon']) ?></div>
                <div class="ribbon-item"><i data-lucide="message-square-text" aria-hidden="true"></i> Bulk SMS</div>
                <div class="ribbon-item"><i data-lucide="phone-call" aria-hidden="true"></i> Voice calls</div>
                <div class="ribbon-item"><i data-lucide="globe" aria-hidden="true"></i> Domains</div>
                <div class="ribbon-item"><i data-lucide="server" aria-hidden="true"></i> Hosting</div>
                <div class="ribbon-item"><i data-lucide="mail" aria-hidden="true"></i> Email</div>
                <div class="ribbon-item"><i data-lucide="panels-top-left" aria-hidden="true"></i> Websites</div>
                <div class="ribbon-item"><i data-lucide="shield-check" aria-hidden="true"></i> Field training</div>
            </div>
        </div>

        <nav class="wrap job-band" aria-label="Start with what you need">
            <a href="service.php?slug=bulk-sms">Send a notice</a>
            <a href="domain.php">Register a name</a>
            <a href="whois.php">WHOIS check</a>
            <a href="service.php?slug=hosting-server">Host a site</a>
            <a href="service.php?slug=professional-email">Open domain email</a>
            <a href="service.php?slug=custom-websites">Book a website</a>
            <a href="service.php?slug=cyber-security">Book training</a>
        </nav>

        <div class="wrap audience-band" aria-label="Who these services are for">
            <span>Built for</span>
            <strong>Cooperatives</strong>
            <strong>Companies</strong>
            <strong>Parties</strong>
            <strong>Schools</strong>
            <strong>Personal use</strong>
        </div>

        <section id="services" class="section services-section">
            <div class="wrap">
                <div class="section-heading section-heading--center reveal">
                    <span class="section-kicker"><?= site_escape($publicSite['home_services_kicker']) ?></span>
                    <h2 class="font-heading"><?= site_escape($publicSite['home_services_heading']) ?></h2>
                    <p><?= site_escape($publicSite['home_services_text']) ?></p>
                </div>

                <div class="service-grid">
                    <?php foreach ($serviceCards as $card): ?>
                        <article class="service-card reveal" id="<?= site_escape($card['slug']) ?>">
                            <span class="service-icon"><i data-lucide="<?= site_escape($card['icon']) ?>" aria-hidden="true"></i></span>
                            <h3 class="font-heading"><?= site_escape($card['title']) ?></h3>
                            <p><?= site_escape($card['summary']) ?></p>
                            <div class="service-tags">
                                <?php foreach ($card['tags'] as $tag): ?>
                                    <span><?= site_escape($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php site_render_service_price(array($card['slug'] => $card['price']), $card['slug'], true); ?>
                            <div class="service-card-actions">
                                <?php if ($card['slug'] === 'domain-registration'): ?>
                                    <a class="button button--small button--primary" href="domain.php">Check a name</a>
                                    <a class="button button--small" href="whois.php">WHOIS</a>
                                <?php else: ?>
                                    <a class="button button--small button--primary" href="service.php?slug=<?= site_escape(rawurlencode($card['slug'])) ?>"><?= site_escape($card['action']) ?></a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="service-pricing-disclaimer">These are list prices. The bill adds 13% VAT. SMS and voice rates fall as the quantity rises. Domain, hosting, server care, and domain email renew that bill from the wallet.</p>
            </div>
        </section>

        <section id="about" class="section about-section">
            <div class="wrap about-layout">
                <div class="about-visual reveal" aria-label="A connected view of our services">
                    <div class="about-orbit" aria-hidden="true"></div>
                    <span class="orbit-label orbit-label--one"><i data-lucide="message-square-text" aria-hidden="true"></i> Messaging</span>
                    <span class="orbit-label orbit-label--two"><i data-lucide="server" aria-hidden="true"></i> Hosting</span>
                    <span class="orbit-label orbit-label--three"><i data-lucide="shield-check" aria-hidden="true"></i> Security</span>
                    <span class="orbit-label orbit-label--four"><i data-lucide="mail" aria-hidden="true"></i> Email</span>
                    <div class="about-core"><i data-lucide="asterisk" aria-hidden="true"></i></div>
                </div>

                <div class="about-copy">
                    <span class="section-kicker reveal">Why <?= site_escape($siteName) ?></span>
                    <h2 class="reveal font-heading"><?= site_escape($publicSite['home_about_heading']) ?></h2>
                    <p class="reveal"><?= site_escape($publicSite['home_about_text']) ?></p>

                    <ul class="value-list">
                        <li class="reveal">
                            <span class="value-list-icon"><i data-lucide="message-circle-check" aria-hidden="true"></i></span>
                            <div><strong>Price before you ask</strong><p>The rate and the 13% VAT bill are on the service page. The checkout form is the brief.</p></div>
                        </li>
                        <li class="reveal">
                            <span class="value-list-icon"><i data-lucide="blocks" aria-hidden="true"></i></span>
                            <div><strong>One catalog</strong><p>Messaging, voice, domains, hosting, email, websites, and on-site training from one company.</p></div>
                        </li>
                        <li class="reveal">
                            <span class="value-list-icon"><i data-lucide="handshake" aria-hidden="true"></i></span>
                            <div><strong>Renews itself</strong><p>After the first wallet top-up is confirmed, domain, hosting, email, and server plans renew on their own.</p></div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="process" class="section process-section">
            <div class="wrap process-layout">
                <div class="process-heading reveal">
                    <span class="section-kicker">How we work</span>
                    <h2 class="font-heading"><?= site_escape($publicSite['home_process_heading']) ?></h2>
                    <p><?= site_escape($publicSite['home_process_text']) ?></p>
                </div>

                <div class="process-steps">
                    <article class="process-step reveal">
                        <span class="step-number">01 / Choose</span>
                        <h3>Pick the service</h3>
                        <p>Open a service and read the rate. A domain is checked on the Domain registration page first. Websites and training are booked from the same catalog.</p>
                    </article>
                    <article class="process-step reveal">
                        <span class="step-number">02 / Wallet</span>
                        <h3>Add wallet funds</h3>
                        <p><?= site_escape($paySentence) ?></p>
                    </article>
                    <article class="process-step reveal">
                        <span class="step-number">03 / Auto-renew</span>
                        <h3>Stay active</h3>
                        <p>Monthly and yearly plans charge the wallet on the due date. A domain year starts when the team marks the registration active. SMS and voice sending opens after identity is approved.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="faq" class="section faq-section">
            <div class="wrap faq-wrap reveal">
                <span class="section-kicker">Questions</span>
                <h2 class="font-heading">Common questions</h2>
                <div class="faq-list">
                    <?php foreach (site_seo_faqs('home') as $faqIndex => $faq): ?>
                        <details class="faq-item"<?= $faqIndex === 0 ? ' open' : '' ?>>
                            <summary><?= site_escape($faq[0]) ?></summary>
                            <p><?= site_escape($faq[1]) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="contact" class="section contact-section">
            <div class="wrap contact-layout">
                <div class="contact-copy reveal">
                    <span class="section-kicker">Get in touch</span>
                    <h2 class="font-heading"><?= site_escape($publicSite['home_contact_heading']) ?></h2>
                    <?php
                    $mailHref = site_mail_href($siteEmail, $siteName);
                    $chatChannels = site_chat_channels($publicSite);
                    $guestChats = site_guest_chats($publicSite);
                    ?>
                    <p><?php if ($guestChats): ?>A visitor does not need a client account. Tap WhatsApp or Messenger and the message opens there.<?php else: ?>A visitor can send a question with the form. No client account is required.<?php endif; ?> A signed-in client can also open a support ticket.</p>
                    <?php if ($guestChats): ?>
                        <?php $guestClass = 'contact-chats'; include __DIR__ . '/includes/site-guest-chat.php'; ?>
                    <?php endif; ?>
                    <?php if ($mailHref !== ''): ?>
                    <a class="contact-method" href="<?= site_escape($mailHref) ?>">
                        <span class="contact-method-icon"><i data-lucide="mail" aria-hidden="true"></i></span>
                        <div><small>Email a query</small><strong><?= site_escape($siteEmail) ?></strong></div>
                    </a>
                    <?php endif; ?>
                    <?php foreach ($chatChannels as $channel): ?>
                    <?php if ($channel['key'] === 'whatsapp' || $channel['key'] === 'messenger') { continue; } ?>
                    <a class="contact-method" href="<?= site_escape($channel['href']) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="contact-method-icon" aria-hidden="true"><?= $channel['icon'] ?></span>
                        <div><small><?= site_escape($channel['note']) ?></small><strong><?= site_escape($channel['label']) ?></strong></div>
                    </a>
                    <?php endforeach; ?>
                    <a class="contact-method" href="client/support.php">
                        <span class="contact-method-icon"><i data-lucide="ticket" aria-hidden="true"></i></span>
                        <div><small>Already a client</small><strong>Support ticket</strong></div>
                    </a>
                    <?php if ($siteLocation !== ''): ?>
                    <div class="contact-method">
                        <span class="contact-method-icon"><i data-lucide="map-pin" aria-hidden="true"></i></span>
                        <div><small>Find our team</small><strong><?= site_escape($siteLocation) ?></strong></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="reveal">
                    <form id="contact-form" method="POST" action="#contact" class="contact-form">
                        <div class="contact-form-header">
                            <h3>Something the catalog does not list</h3>
                            <p>Fields marked with * are required.</p>
                        </div>

                        <?php if ($error !== ''): ?>
                            <div class="form-status form-status--error" role="alert">
                                <i data-lucide="circle-alert" aria-hidden="true"></i>
                                <span><?= site_escape($error) ?></span>
                            </div>
                        <?php elseif ($success !== ''): ?>
                            <div class="form-status form-status--success" role="status">
                                <i data-lucide="circle-check" aria-hidden="true"></i>
                                <span><?= site_escape($success) ?></span>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="csrf_token" value="<?= site_escape($_SESSION['contact_csrf']) ?>">
                        <div class="honeypot" aria-hidden="true">
                            <label for="website_url">Leave this field empty</label>
                            <input id="website_url" type="text" name="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="name">Full name *</label>
                                <input id="name" type="text" name="name" maxlength="140" required autocomplete="name"
                                       placeholder="Your name" value="<?= site_escape($formValues['name']) ?>">
                            </div>
                            <div class="form-field">
                                <label for="email">Email *</label>
                                <input id="email" type="email" name="email" maxlength="254" required autocomplete="email"
                                       placeholder="you@company.com" value="<?= site_escape($formValues['email']) ?>">
                            </div>
                            <div class="form-field form-field--full">
                                <label for="service">What can we help with?</label>
                                <select id="service" name="service">
                                    <option value="">Choose a service (optional)</option>
                                    <?php foreach ($contactServices as $service): ?>
                                        <option value="<?= site_escape($service) ?>" <?= $formValues['service'] === $service ? 'selected' : '' ?>>
                                            <?= site_escape($service === 'Other' ? 'Something else' : $service) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field form-field--full">
                                <label for="message">A few details *</label>
                                <textarea id="message" name="message" maxlength="3000" rows="4" required
                                          placeholder="Tell us what you are working on..."><?= site_escape($formValues['message']) ?></textarea>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="human_check">What is <?= site_escape(auth_math_prompt('contact')) ?>? *</label>
                            <input id="human_check" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" placeholder="Answer">
                        </div>

                        <button class="button button--primary form-submit" type="submit" name="submit_contact" value="1">
                            Send your message
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </button>
                        <p class="form-note">Your name and email are used only to reply to this enquiry. A mobile number is not required. Sending the message is your consent for that use. Read the <a href="privacy.php">privacy policy</a> and the <a href="cookies.php">cookie notice</a>.</p>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>