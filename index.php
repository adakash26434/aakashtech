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
        echo '<p class="service-start">Starts from <strong>' . site_escape($shown) . '</strong></p>';
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
$sitePhone = $publicSite['site_phone'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
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
    if (!hash_equals($_SESSION['contact_csrf'], $submittedToken)) {
        $error = 'Your form session expired. Please refresh the page and try again.';
    } elseif (site_posted_value('website_url') !== '') {
        $success = 'Thanks for reaching out. We will be in touch soon.';
        $formValues = array_fill_keys(array_keys($formValues), '');
    } elseif (
        $formValues['name'] === '' ||
        $formValues['email'] === '' ||
        $formValues['phone'] === '' ||
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
            $stmt->bind_param(
                'sssss',
                $formValues['name'],
                $formValues['email'],
                $formValues['phone'],
                $formValues['service'],
                $formValues['message']
            );

            if (auth_attempt_blocked($conn, 'contact', 5, 1800)) {
                $error = 'Too many messages from this network. Please wait and try again.';
            } elseif ($stmt->execute()) {
                auth_note_attempt($conn, 'contact');
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
    <title><?= site_escape($siteName) ?> | SMS, Voice, Websites, Email &amp; Training in Nepal</title>
    <meta name="description" content="Bulk SMS and auto voice calls for cooperatives, companies, and parties in Nepal, plus domains, hosting, Zoho email, custom websites, and on-site cyber training.">
    <meta property="og:title" content="Aakash Technologies | SMS, Voice, Websites, Email &amp; Training in Nepal">
    <meta property="og:description" content="See the rate, then buy or book online. Volume SMS and voice, websites, Zoho email, and field cyber training.">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['Space Grotesk', 'sans-serif'],
                        body: ['Inter', 'sans-serif']
                    },
                    colors: {
                        brand: {
                            50: '#e5f5f0',
                            100: '#d2eee6',
                            200: '#a8dfd0',
                            300: '#79cdb7',
                            400: '#43b39a',
                            500: '#0b8b7a',
                            600: '#087365',
                            700: '#075e54'
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/site.css">
    <script defer src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="assets/js/site.js"></script>
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
                        For cooperatives, companies, parties, and personal work
                    </div>
                    <h1 class="reveal font-heading">Send the notice. Stay online. Train the <span>people.</span></h1>
                    <p class="hero-lede reveal">
                        Bulk SMS and auto voice calls for an AGM, program, event, election, or festival. Domain, hosting, and server care. Zoho email on your own domain. Websites for a cooperative, company, school, hotel, or newsroom. Cyber training at your address.
                    </p>
                    <div class="hero-actions reveal">
                        <a class="button button--primary" href="#services">
                            See who it is for
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

                <div class="hero-art reveal" aria-label="Aakash Technologies services">
                    <div class="art-orbit" aria-hidden="true"></div>
                    <span class="art-spark art-spark--one" aria-hidden="true"></span>
                    <span class="art-spark art-spark--two" aria-hidden="true"></span>

                    <div class="solution-window">
                        <div class="window-top">
                            <div class="window-brand">
                                <span class="window-brand-mark"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                                Aakash Technologies
                            </div>
                            <div class="window-dots" aria-hidden="true"><span></span><span></span><span></span></div>
                        </div>
                    <h2 class="window-heading font-heading">Everything is on the page</h2>
                        <p class="window-subtitle">Read the rate, then buy or book. No extra call to explain the service.</p>
                        <div class="solution-list">
                            <div class="solution-row">
                                <span class="solution-icon"><i data-lucide="message-square-text" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Bulk SMS</strong><span>Cheaper as the list gets longer</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </div>
                            <div class="solution-row">
                                <span class="solution-icon"><i data-lucide="phone-call" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Auto voice calls</strong><span>AGM, election, festival, program</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </div>
                            <div class="solution-row">
                                <span class="solution-icon"><i data-lucide="server" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Sites, hosting, Zoho email</strong><span>Domain mail managed in Nepal</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </div>
                            <div class="solution-row">
                                <span class="solution-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
                                <span class="solution-row-copy"><strong>Field cyber training</strong><span>Directors, staff, and members</span></span>
                                <i class="solution-row-arrow" data-lucide="arrow-up-right" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>

                    <div class="art-note">
                        <i data-lucide="circle-check" aria-hidden="true"></i>
                        Plan clearly. Build thoughtfully.
                    </div>
                </div>
            </div>
        </section>

        <div class="service-ribbon" aria-label="Aakash Technologies service categories">
            <div class="wrap service-ribbon-inner">
                <div class="ribbon-lead">Buy what you need. Recurring plans renew themselves.</div>
                <div class="ribbon-item"><i data-lucide="message-square-text" aria-hidden="true"></i> Bulk SMS</div>
                <div class="ribbon-item"><i data-lucide="phone-call" aria-hidden="true"></i> Voice calls</div>
                <div class="ribbon-item"><i data-lucide="globe" aria-hidden="true"></i> Domains</div>
                <div class="ribbon-item"><i data-lucide="server" aria-hidden="true"></i> Hosting</div>
                <div class="ribbon-item"><i data-lucide="mail" aria-hidden="true"></i> Email</div>
                <div class="ribbon-item"><i data-lucide="panels-top-left" aria-hidden="true"></i> Websites</div>
                <div class="ribbon-item"><i data-lucide="shield-check" aria-hidden="true"></i> Field training</div>
            </div>
        </div>

        <div class="wrap audience-band" aria-label="Who these services are for">
            <span>Built for</span>
            <strong>Cooperatives</strong>
            <strong>Companies</strong>
            <strong>Parties</strong>
            <strong>Personal use</strong>
        </div>

        <section id="services" class="section services-section">
            <div class="wrap">
                <div class="section-heading section-heading--center reveal">
                    <span class="section-kicker">What we do</span>
                    <h2 class="font-heading">Read the rate. Buy it, or book it.</h2>
                    <p>Each service page says who it is for, what is included, and the price. Checkout asks for the details we need, so the order does not depend on a follow-up call.</p>
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
                                <a class="button button--small button--primary" href="service.php?slug=<?= site_escape(rawurlencode($card['slug'])) ?>">See rates</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="service-pricing-disclaimer">SMS and voice rates fall as the quantity rises. Every rate on this site can be changed from Admin, then Billing. Domain, hosting, server care, and Zoho email renew from the wallet.</p>
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
                    <span class="section-kicker reveal">Why Aakash Technologies</span>
                    <h2 class="reveal font-heading">The rate is on the page. The order is the brief.</h2>
                    <p class="reveal">Each service page lists who it is for, what you type before paying, and the price. Cooperatives, companies, parties, and personal use follow the same path.</p>

                    <ul class="value-list">
                        <li class="reveal">
                            <span class="value-list-icon"><i data-lucide="message-circle-check" aria-hidden="true"></i></span>
                            <div><strong>No discovery call</strong><p>The checkout form is the brief for SMS, voice, domains, email, websites, and field training.</p></div>
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
                    <h2 class="font-heading">Buy it, then let it renew.</h2>
                    <p>Create a client account, add wallet funds once, and choose a plan. Recurring services continue without a renewal ticket.</p>
                </div>

                <div class="process-steps">
                    <article class="process-step reveal">
                        <span class="step-number">01 / Choose</span>
                        <h3>Pick the service</h3>
                        <p>Open a service, read the rate, and enter the details that page asks for. Websites and training are booked the same way.</p>
                    </article>
                    <article class="process-step reveal">
                        <span class="step-number">02 / Pay once</span>
                        <h3>Add wallet funds</h3>
                        <p>Pay by eSewa, Khalti, or bank. After that payment is confirmed, checkout is immediate.</p>
                    </article>
                    <article class="process-step reveal">
                        <span class="step-number">03 / Auto-renew</span>
                        <h3>Stay active</h3>
                        <p>Monthly and yearly plans charge the wallet on the due date and extend themselves.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="contact" class="section contact-section">
            <div class="wrap contact-layout">
                <div class="contact-copy reveal">
                    <span class="section-kicker">Get in touch</span>
                    <h2 class="font-heading">Rates and orders are already online.</h2>
                    <p>Buy or book from the service pages. For a query, email us or message on WhatsApp. Use the form when you want the request kept in writing.</p>

                    <?php
                    $mailHref = site_mail_href($siteEmail, $siteName);
                    $whatsappShown = $siteWhatsapp !== '' ? $siteWhatsapp : $sitePhone;
                    $whatsappHref = site_whatsapp_href($whatsappShown);
                    ?>
                    <?php if ($mailHref !== ''): ?>
                    <a class="contact-method" href="<?= site_escape($mailHref) ?>">
                        <span class="contact-method-icon"><i data-lucide="mail" aria-hidden="true"></i></span>
                        <div><small>Email a query</small><strong><?= site_escape($siteEmail) ?></strong></div>
                    </a>
                    <?php endif; ?>
                    <?php if ($whatsappHref !== ''): ?>
                    <a class="contact-method" href="<?= site_escape($whatsappHref) ?>" target="_blank" rel="noopener noreferrer">
                        <span class="contact-method-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.2L1 23l5.9-1.1A11 11 0 0 0 20.5 3.5zM12 20.3a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3.5.7.7-3.4-.2-.3A8.3 8.3 0 1 1 12 20.3zm4.6-6.2c-.3-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.6.1a6.8 6.8 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.5.2-.3a.5.5 0 0 0 0-.5c-.1-.1-.6-1.4-.8-1.9s-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.5 4 4.2 4.2 0 0 0 3 .4 2.5 2.5 0 0 0 1.6-1.2 2 2 0 0 0 .1-1.2c-.1-.1-.3-.2-.6-.3z"/></svg></span>
                        <div><small>WhatsApp a query</small><strong><?= site_escape($whatsappShown) ?></strong></div>
                    </a>
                    <?php endif; ?>
                    <?php if ($sitePhone !== ''): ?>
                    <a class="contact-method" href="tel:<?= site_escape(preg_replace('/\s+/', '', $sitePhone)) ?>">
                        <span class="contact-method-icon"><i data-lucide="phone" aria-hidden="true"></i></span>
                        <div><small>Call us</small><strong><?= site_escape($sitePhone) ?></strong></div>
                    </a>
                    <?php endif; ?>
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
                                <label for="phone">Phone *</label>
                                <input id="phone" type="tel" name="phone" maxlength="40" required autocomplete="tel"
                                       placeholder="+977 ..." value="<?= site_escape($formValues['phone']) ?>">
                            </div>
                            <div class="form-field form-field--full">
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

                        <button class="button button--primary form-submit" type="submit" name="submit_contact" value="1">
                            Send your message
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </button>
                        <p class="form-note">Your details are used only to respond to this enquiry.</p>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>