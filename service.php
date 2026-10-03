<?php
require_once __DIR__ . '/includes/session.php';

require_once __DIR__ . '/includes/billing.php';

function service_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$definitions = billing_service_definitions();
$pages = billing_page_copy();
$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? $_GET['slug'] : '';
if (!isset($definitions[$slug]) || !isset($pages[$slug])) {
    header('Location: index.php#services');
    exit;
}

$page = $pages[$slug];
$guides = billing_service_guide();
$guide = isset($guides[$slug]) ? $guides[$slug] : array('includes' => array(), 'steps' => array(), 'notes' => array(), 'plans' => array(), 'next' => array());
$publicSite = site_public_defaults();
$conn = null;
try {
    require_once __DIR__ . '/config.php';
    $publicSite = site_public_settings($conn);
} catch (Throwable $exception) {
    error_log('Service page is showing published rates.');
}
$siteName = $publicSite['site_name'];
$siteEmail = $publicSite['site_email'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
$overrides = $conn ? billing_catalog_overrides($conn) : array();
if ($conn) {
    $page = billing_public_page($conn, $slug);
}
$service = billing_saved_service_view($conn, $slug, $overrides);
$servicePoster = $conn ? site_service_poster($conn, $slug) : '';

$plans = array();
foreach (($conn ? billing_load_plans($conn) : billing_default_plans()) as $plan) {
    if ($plan['service_slug'] === $slug && (!isset($plan['is_active']) || (int) $plan['is_active'] === 1)) {
        $plans[] = $plan;
    }
}
$slabs = ($slug === 'bulk-sms' || $slug === 'bulk-voice') ? billing_slabs_for($conn, $slug) : array();
$navBase = 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    require_once __DIR__ . '/includes/seo.php';
    $seoPhrases = site_seo_phrases();
    $seoPhrase = isset($seoPhrases[$slug]) ? $seoPhrases[$slug] : array('title' => $service['title'], 'description' => $page['lead']);
    $seoSameAs = array();
    foreach ($siteSocials as $social) {
        $seoSameAs[] = $social['href'];
    }
    $seoPrice = null;
    $seoUnit = '';
    if ($slabs) {
        $seoStart = billing_start_slab($slabs);
        $seoPrice = billing_selling_price($seoStart['unit_price'], isset($seoStart['offer_price']) ? $seoStart['offer_price'] : 0);
        $seoUnit = $slug === 'bulk-voice' ? 'call' : 'SMS';
    } else {
        foreach ($plans as $seoPlan) {
            if ((float) $seoPlan['price'] <= 0) {
                continue;
            }
            $seoSell = billing_selling_price($seoPlan['price'], isset($seoPlan['offer_price']) ? $seoPlan['offer_price'] : 0);
            if ($seoPrice === null || $seoSell < $seoPrice) {
                $seoPrice = $seoSell;
            }
        }
    }
    site_seo_print(
        $seoPhrase['title'] . ' | ' . $siteName,
        $seoPhrase['description'],
        'service.php?slug=' . rawurlencode($slug),
        site_seo_service_graph($publicSite, $seoSameAs, $slug, $seoPhrase['description'], $seoPrice, $seoUnit),
        $servicePoster !== '' ? $servicePoster : $siteLogo
    );
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="wrap">
            <?php if ($servicePoster !== ''): ?>
                <figure class="detail-poster">
                    <img src="<?= service_escape($servicePoster) ?>" alt="<?= service_escape($service['title']) ?>">
                </figure>
            <?php endif; ?>
            <div class="detail-layout">
                <div>
                    <a class="detail-back" href="index.php#services">All services</a>
                    <span class="section-kicker"><?= service_escape($page['kicker']) ?></span>
                    <h1 class="font-heading"><?= service_escape($service['title']) ?></h1>
                    <p class="detail-lead"><?= service_escape($page['lead']) ?></p>
                    <?php if ($slug === 'domain-registration'): ?>
                        <a class="button button--primary detail-cta" href="domain.php">Check a name</a>
                        <a class="detail-back" href="whois.php">WHOIS check up</a>
                    <?php else: ?>
                        <a class="button button--primary detail-cta" href="#buy"><?= service_escape($service['action']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($guide['includes'])): ?>
                        <h2 class="detail-subhead font-heading">Included</h2>
                        <ul class="include-list">
                            <?php foreach ($guide['includes'] as $item): ?>
                                <li><?= service_escape($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($guide['steps'])): ?>
                        <h2 class="detail-subhead font-heading">How it works</h2>
                        <ol class="plain-steps">
                            <?php foreach ($guide['steps'] as $step): ?>
                                <li><?= service_escape($step) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <?php $publicNotes = !empty($page['points_saved']) ? $page['points'] : (isset($guide['notes']) ? $guide['notes'] : array()); ?>
                    <?php if ($publicNotes): ?>
                        <h2 class="detail-subhead font-heading">Worth knowing</h2>
                        <ul class="detail-points">
                            <?php foreach ($publicNotes as $note): ?>
                                <li><?= service_escape($note) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($page['examples'])): ?>
                        <h2 class="detail-subhead font-heading">A notice you can copy</h2>
                        <p class="detail-lead"><?php if ($slug === 'bulk-voice'): ?>Replace the words in brackets. Each number hears this same script. The team places the call from the job saved under Messages.<?php else: ?>Replace the words in brackets. Each number receives this same notice. Sending is done in the client SMS dashboard.<?php endif; ?></p>
                        <?php foreach ($page['examples'] as $example): ?>
                            <figure class="notice-sample">
                                <figcaption><?= service_escape($example[0]) ?></figcaption>
                                <blockquote><?= service_escape($example[1]) ?></blockquote>
                            </figure>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($guide['next'])): ?>
                        <h2 class="detail-subhead font-heading">Often added with this</h2>
                        <div class="next-services">
                            <?php foreach ($guide['next'] as $next): ?>
                                <?php
                                $nextSlug = $next[0];
                                $nextTitle = isset($definitions[$nextSlug]) ? $definitions[$nextSlug]['title'] : $next[1];
                                $nextHref = $nextSlug === 'domain-registration' ? 'domain.php' : ('service.php?slug=' . rawurlencode($nextSlug));
                                ?>
                                <a href="<?= service_escape($nextHref) ?>">
                                    <strong><?= service_escape($nextTitle) ?></strong>
                                    <span><?= service_escape($next[1]) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php $faqs = site_seo_faqs($slug); ?>
                    <?php if ($faqs): ?>
                        <h2 class="detail-subhead font-heading">Common questions</h2>
                        <dl class="detail-faq">
                            <?php foreach ($faqs as $faq): ?>
                                <dt><?= service_escape($faq[0]) ?></dt>
                                <dd><?= service_escape($faq[1]) ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </div>
                <aside class="detail-panel" id="buy">
                    <?php if ($slabs): ?>
                        <h2 class="font-heading">Rate by volume</h2>
                        <p>Use the row that contains your quantity. That row is the price for each one. A quantity outside the table cannot be ordered. The bill adds 13% VAT to that amount.</p>
                        <table class="slab-table">
                            <thead><tr><th>Quantity</th><th>Each</th></tr></thead>
                            <tbody>
                                <?php foreach ($slabs as $slab): ?>
                                    <tr data-min="<?= (int) $slab['min_qty'] ?>" data-max="<?= (int) $slab['max_qty'] ?>"<?= !empty($slab['is_start']) ? ' class="slab-row--start"' : '' ?>>
                                        <td><?= number_format($slab['min_qty']) ?>–<?= number_format($slab['max_qty']) ?></td>
                                        <td><?= billing_rate_markup($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0, true) ?><?php if (!empty($slab['is_start'])): ?> <span class="slab-start">Starts from</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        $sample = billing_start_slab($slabs);
                        $sampleQty = (int) $sample['min_qty'];
                        $sampleUnit = billing_selling_price($sample['unit_price'], isset($sample['offer_price']) ? $sample['offer_price'] : 0);
                        $sampleBill = billing_vat_bill(round($sampleUnit * $sampleQty, 2));
                        $sampleName = $slug === 'bulk-voice' ? 'calls' : 'SMS';
                        $rateRows = array();
                        foreach ($slabs as $slab) {
                            $rateRows[] = array(
                                'min' => (int) $slab['min_qty'],
                                'max' => (int) $slab['max_qty'],
                                'unit' => (float) billing_selling_price($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0)
                            );
                        }
                        ?>
                        <div class="bill-calc" data-rates="<?= service_escape(json_encode($rateRows)) ?>" data-unit-name="<?= service_escape($sampleName) ?>">
                            <label for="bill-qty">See the bill for a quantity</label>
                            <input id="bill-qty" type="number" inputmode="numeric" min="1" step="1" value="<?= $sampleQty ?>">
                            <p class="bill-example" data-bill-result>Example for <?= number_format($sampleQty) ?> <?= service_escape($sampleName) ?>: <?= service_escape(billing_money_label($sampleBill['net'])) ?> plus VAT <?= service_escape(billing_money_label($sampleBill['vat'])) ?>. The bill is <?= service_escape(billing_money_label($sampleBill['total'])) ?>.</p>
                        </div>
                    <?php else: ?>
                        <h2 class="font-heading">Choose a package</h2>
                        <p>The amount below is the list price. The bill adds 13% VAT. Yearly and monthly plans renew that bill on the due date. A one-time booking is paid once.</p>
                    <?php endif; ?>
                    <div class="detail-plans">
                        <?php foreach ($plans as $plan): ?>
                            <article>
                                <h3 class="font-heading"><?= service_escape($plan['name']) ?></h3>
                                <?php if (!empty($guide['plans'][$plan['code']])): ?>
                                    <p class="plan-fit"><?= service_escape($guide['plans'][$plan['code']]) ?></p>
                                <?php endif; ?>
                                <p><?= service_escape($plan['summary']) ?></p>
                                <div class="plan-buy">
                                    <?php if ((float) $plan['price'] > 0): ?>
                                        <?php $planBill = billing_vat_bill(billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0)); ?>
                                        <span>
                                            <strong><?= billing_rate_markup($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0, false, billing_cycle_suffix($plan['billing_cycle'])) ?></strong>
                                            <span class="bill-example">Bill <?= service_escape(billing_money_label($planBill['total'])) ?> with 13% VAT</span>
                                        </span>
                                    <?php else: ?>
                                        <?php $startSlab = $slabs ? billing_start_slab($slabs) : null; ?>
                                        <strong><?php if ($startSlab): ?>Starts from <?= billing_rate_markup($startSlab['unit_price'], isset($startSlab['offer_price']) ? $startSlab['offer_price'] : 0, true, ' each') ?><?php else: ?>Volume rate<?php endif; ?></strong>
                                    <?php endif; ?>
                                    <?php
                                    $planHref = billing_buy_href_plan($plan['code']);
                                    $planAction = $service['action'];
                                    if ($plan['code'] === 'domain-com' || $plan['code'] === 'domain-np') {
                                        $planHref = $plan['code'] === 'domain-np' ? 'domain.php' : 'domain.php?tld=com';
                                        $planAction = 'Check this name';
                                    }
                                    ?>
                                    <a class="button button--small button--primary" href="<?= service_escape($planHref) ?>"><?= service_escape($planAction) ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>
            </div>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
