<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/shop-view.php';

$cid = (int) get_client_id();
$balance = billing_balance($conn, $cid);
$services = billing_service_definitions();
$requested = isset($_GET['service']) ? (string) $_GET['service'] : '';
if ($requested !== '' && !isset($services[$requested])) {
    $requested = '';
}

$grouped = array();
$counts = array();
foreach (billing_load_plans($conn) as $plan) {
    if ((int) $plan['is_active'] !== 1) {
        continue;
    }
    $counts[$plan['service_slug']] = (isset($counts[$plan['service_slug']]) ? $counts[$plan['service_slug']] : 0) + 1;
    if ($requested !== '' && $plan['service_slug'] !== $requested) {
        continue;
    }
    $grouped[$plan['service_slug']][] = $plan;
}
$identityNote = shop_identity_note($conn, $cid, array_keys($grouped));
?>
<div class="shop-head">
    <div>
        <h1 class="shop-title">Buy a service</h1>
        <p class="shop-sub">Pick a plan, fill in the details, pay from your wallet. We set it up and you follow it in My Services.</p>
    </div>
    <a href="wallet.php" class="shop-wallet" aria-label="Wallet balance <?= e(billing_money_label($balance)) ?>. Open wallet.">
        <span>Wallet</span><strong><?= e(billing_money_label($balance)) ?></strong><em>Add funds</em>
    </a>
</div>

<ol class="shop-steps" aria-label="How buying works">
    <li><b>1</b> Choose a plan</li>
    <li><b>2</b> Fill in the details</li>
    <li><b>3</b> Pay from your wallet</li>
    <li><b>4</b> Track it in My Services</li>
</ol>

<?php if ($identityNote !== ''): ?>
    <div class="shop-note" role="note"><i data-lucide="badge-check"></i><p><?= e($identityNote) ?> <a href="kyc.php">Approve identity</a></p></div>
<?php endif; ?>

<nav class="shop-tabs" aria-label="Services">
    <a href="shop.php" class="<?= $requested === '' ? 'is-on' : '' ?>"<?= $requested === '' ? ' aria-current="page"' : '' ?>><i data-lucide="layout-grid"></i>All<span><?= (int) array_sum($counts) ?></span></a>
    <?php foreach ($services as $slug => $service): ?>
        <?php if (empty($counts[$slug])) { continue; } ?>
        <a href="shop.php?service=<?= e(rawurlencode($slug)) ?>" class="<?= $requested === $slug ? 'is-on' : '' ?>"<?= $requested === $slug ? ' aria-current="page"' : '' ?>><i data-lucide="<?= e($service['icon']) ?>"></i><?= e($service['title']) ?><span><?= (int) $counts[$slug] ?></span></a>
    <?php endforeach; ?>
</nav>

<?php if (!$grouped): ?>
    <div class="shop-empty"><h2>Nothing to buy right now</h2><p>No plans are open at the moment. Write to us from Support and we will help.</p><a href="support.php" class="shop-btn">Contact support</a></div>
<?php endif; ?>

<div class="shop-sections">
<?php foreach ($services as $slug => $service): ?>
    <?php if (empty($grouped[$slug])) { continue; } ?>
    <?php
    $plans = $grouped[$slug];
    $bands = ($slug === 'bulk-sms' || $slug === 'bulk-voice') ? billing_slabs_for($conn, $slug) : array();
    $startBand = $bands ? billing_start_slab($bands) : null;
    $bestCode = shop_best_value_code($plans);
    ?>
    <section class="shop-section" id="svc-<?= e($slug) ?>" aria-labelledby="h-<?= e($slug) ?>">
        <header class="shop-section-head">
            <span class="shop-icon"><i data-lucide="<?= e($service['icon']) ?>"></i></span>
            <div>
                <h2 id="h-<?= e($slug) ?>"><?= e($service['title']) ?></h2>
                <p><?= e($service['summary']) ?></p>
            </div>
            <a class="shop-more" href="../service.php?slug=<?= e(rawurlencode($slug)) ?>">What is included</a>
        </header>
        <div class="shop-grid">
        <?php foreach ($plans as $plan): ?>
            <?php
            $facts = shop_plan_facts($plan, $balance);
            $planHref = 'checkout.php?plan=' . rawurlencode($plan['code']);
            $planLabel = $service['action'];
            if ($plan['code'] === 'domain-com' || $plan['code'] === 'domain-np') {
                $planHref = $plan['code'] === 'domain-np' ? '../domain.php' : '../domain.php?tld=com';
                $planLabel = 'Check this name';
            }
            $suffix = billing_cycle_suffix($plan['billing_cycle']);
            ?>
            <article class="shop-card<?= $bestCode === $plan['code'] ? ' is-best' : '' ?>">
                <div class="shop-badges">
                    <?php if ($bestCode === $plan['code']): ?><span class="shop-badge is-best">Lowest cost per mailbox</span><?php endif; ?>
                    <?php if ($facts['offer']): ?><span class="shop-badge is-offer">Offer: save <?= (int) $facts['save_percent'] ?>%</span><?php endif; ?>
                    <span class="shop-badge"><?= e(shop_cycle_chip($plan['billing_cycle'], (int) $plan['auto_renew_default'] === 1)) ?></span>
                </div>
                <h3><?= e($plan['name']) ?></h3>
                <p class="shop-card-text"><?= e($plan['summary']) ?></p>

                <?php if ($facts['priced']): ?>
                    <p class="shop-price"><?= billing_rate_markup($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0, false, $suffix) ?></p>
                    <?php if ($facts['per_unit'] !== null): ?><p class="shop-per"><?= e(billing_money_label($facts['per_unit'])) ?> <?= e($facts['per_unit_label']) ?></p><?php endif; ?>
                    <p class="shop-vat">You pay <b><?= e(billing_money_label($facts['bill']['total'])) ?></b> with 13% VAT</p>
                    <?php if ($facts['covered']): ?>
                        <p class="shop-wallet-state is-ok"><i data-lucide="check-circle-2"></i>Your wallet covers this</p>
                    <?php else: ?>
                        <p class="shop-wallet-state is-short"><i data-lucide="wallet"></i>Add <?= e(billing_money_label($facts['short_by'])) ?> to your wallet first <a href="wallet.php">Add funds</a></p>
                    <?php endif; ?>
                <?php elseif ($startBand): ?>
                    <p class="shop-price">From <?= billing_rate_markup($startBand['unit_price'], isset($startBand['offer_price']) ? $startBand['offer_price'] : 0, true, ' each') ?></p>
                    <p class="shop-vat">The more you buy, the lower the rate. 13% VAT is added to the total.</p>
                    <div class="shop-ladder-wrap"><table class="shop-ladder">
                        <caption class="sr-only">Rate by quantity</caption>
                        <thead><tr><th scope="col">Quantity</th><th scope="col">Each</th></tr></thead>
                        <tbody>
                        <?php foreach ($bands as $band): ?>
                            <tr><th scope="row"><?= number_format($band['min_qty']) ?> to <?= number_format($band['max_qty']) ?></th><td><?= billing_rate_markup($band['unit_price'], isset($band['offer_price']) ? $band['offer_price'] : 0, true, '') ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php else: ?>
                    <p class="shop-price">Volume rate</p>
                    <p class="shop-vat">The price is shown on the next page, for the quantity you choose.</p>
                <?php endif; ?>
                <a href="<?= e($planHref) ?>" class="shop-btn"><?= e($planLabel) ?></a>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>

<p class="shop-help">Not sure which plan fits? <a href="support.php">Ask us</a>. We reply with a recommendation, no charge.</p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
