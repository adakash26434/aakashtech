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

$service = $definitions[$slug];
$page = $pages[$slug];
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
$sitePhone = $publicSite['site_phone'];
$siteWhatsapp = $publicSite['whatsapp_number'];
$siteSocials = site_social_links($publicSite);
$siteLocation = $publicSite['site_location'];
$siteTagline = $publicSite['footer_tagline'];
$siteFooter = $publicSite['footer_text'];
$siteLogo = $publicSite['logo_path'];
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
    <title><?= service_escape($service['title']) ?> | Aakash Technologies</title>
    <meta name="description" content="<?= service_escape($page['lead']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <ul class="detail-points">
                        <?php foreach ($page['points'] as $point): ?>
                            <li><?= service_escape($point) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php $asks = billing_checkout_asks($slug); ?>
                    <?php if ($asks): ?>
                        <h2 class="detail-subhead font-heading">What you enter before paying</h2>
                        <ul class="detail-points">
                            <?php foreach ($asks as $ask): ?>
                                <li><?= service_escape($ask) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <h2 class="detail-subhead font-heading">How you buy it</h2>
                    <ul class="detail-points">
                        <?php foreach (billing_buy_steps() as $step): ?>
                            <li><?= service_escape($step) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (!empty($page['after'])): ?>
                        <h2 class="detail-subhead font-heading">After you pay</h2>
                        <ul class="detail-points">
                            <?php foreach ($page['after'] as $after): ?>
                                <li><?= service_escape($after) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <aside class="detail-panel">
                    <?php if ($slabs): ?>
                        <h2 class="font-heading">Rate by volume</h2>
                        <p>Use the row that contains your quantity. That row is the price for each one. A quantity outside the table cannot be ordered.</p>
                        <table class="slab-table">
                            <thead><tr><th>Quantity</th><th>Each</th></tr></thead>
                            <tbody>
                                <?php foreach ($slabs as $slab): ?>
                                    <tr<?= !empty($slab['is_start']) ? ' class="slab-row--start"' : '' ?>>
                                        <td><?= number_format($slab['min_qty']) ?>–<?= number_format($slab['max_qty']) ?></td>
                                        <td><?= service_escape(billing_unit_label($slab['unit_price'])) ?><?php if (!empty($slab['is_start'])): ?> <span class="slab-start">Starts from</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <h2 class="font-heading">Choose a package</h2>
                        <p>The amount below is taken from your wallet. Yearly and monthly plans renew on the due date. A one-time booking is paid once.</p>
                    <?php endif; ?>
                    <div class="detail-plans">
                        <?php foreach ($plans as $plan): ?>
                            <article>
                                <h3 class="font-heading"><?= service_escape($plan['name']) ?></h3>
                                <p><?= service_escape($plan['summary']) ?></p>
                                <div class="plan-buy">
                                    <?php if ((float) $plan['price'] > 0): ?>
                                        <strong><?= service_escape(billing_money_label($plan['price'])) ?><?= service_escape(billing_cycle_suffix($plan['billing_cycle'])) ?></strong>
                                    <?php else: ?>
                                        <strong>Starts from <?= service_escape(billing_unit_label(billing_start_slab($slabs)['unit_price'])) ?> each</strong>
                                    <?php endif; ?>
                                    <a class="button button--small button--primary" href="<?= service_escape(billing_buy_href_plan($plan['code'])) ?>"><?= service_escape($service['action']) ?></a>
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
