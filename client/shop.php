<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$balance = billing_balance($conn, $cid);
$services = billing_service_definitions();
$requested = isset($_GET['service']) ? (string) $_GET['service'] : '';
if ($requested !== '' && !isset($services[$requested])) {
    $requested = '';
}

$grouped = array();
foreach (billing_load_plans($conn) as $plan) {
    if ((int) $plan['is_active'] !== 1) {
        continue;
    }
    if ($requested !== '' && $plan['service_slug'] !== $requested) {
        continue;
    }
    $grouped[$plan['service_slug']][] = $plan;
}
?>
<div class="mb-8 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Buy a service</h1>
        <p class="text-slate-500 text-sm">Open a service to read what is included, then continue. The next page asks for every detail.</p>
    </div>
    <a href="wallet.php" class="px-4 py-2 rounded-xl bg-brand-500/15 text-brand-400 text-sm font-medium">Wallet <?= e(billing_money_label($balance)) ?></a>
</div>

<?php if ($requested !== ''): ?>
    <p class="mb-6 text-sm"><a href="shop.php" class="text-brand-400">Show every service</a></p>
<?php endif; ?>

<div class="space-y-8">
    <?php foreach ($services as $slug => $service): ?>
        <?php if (empty($grouped[$slug])) { continue; } ?>
        <section>
            <div class="flex items-end justify-between gap-3 flex-wrap mb-4">
                <div>
                    <h2 class="font-heading font-semibold text-white text-lg mb-1"><?= e($service['title']) ?></h2>
                    <p class="text-slate-500 text-sm"><?= e($service['summary']) ?></p>
                </div>
                <a href="../service.php?slug=<?= e(rawurlencode($slug)) ?>" class="text-brand-400 text-sm">Full explanation</a>
            </div>
            <?php
            $bands = ($slug === 'bulk-sms' || $slug === 'bulk-voice') ? billing_slabs_for($conn, $slug) : array();
            $startBand = $bands ? billing_start_slab($bands) : null;
            ?>
            <div class="grid md:grid-cols-2 gap-4">
                <?php foreach ($grouped[$slug] as $plan): ?>
                    <article class="dash-panel">
                        <div class="p-5 flex flex-col h-full">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h3 class="font-heading font-semibold text-white"><?= e($plan['name']) ?></h3>
                                <?php if ((int) $plan['auto_renew_default'] === 1): ?>
                                    <span class="px-2 py-1 text-[10px] font-medium rounded-full bg-brand-500/20 text-brand-400">Auto-renew</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-[10px] font-medium rounded-full bg-slate-500/15 text-slate-400">One time</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-slate-500 text-sm mb-4"><?= e($plan['summary']) ?></p>
                            <?php if ((float) $plan['price'] > 0): ?>
                                <?php $shopBill = billing_vat_bill(billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0)); ?>
                                <p class="font-heading font-bold text-white text-xl mb-1"><?= billing_rate_markup($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0, false, billing_cycle_suffix($plan['billing_cycle'])) ?></p>
                                <p class="text-slate-500 text-xs mb-4">Bill <?= e(billing_money_label($shopBill['total'])) ?> after 13% VAT</p>
                            <?php elseif ($startBand): ?>
                                <p class="font-heading font-bold text-white text-xl mb-1">Starts from <?= billing_rate_markup($startBand['unit_price'], isset($startBand['offer_price']) ? $startBand['offer_price'] : 0, true, ' each') ?></p>
                                <p class="text-slate-500 text-xs mb-2">The bill adds 13% VAT to the quantity total.</p>
                                <ul class="text-slate-500 text-xs space-y-1 mb-4">
                                    <?php foreach ($bands as $band): ?>
                                        <li><?= number_format($band['min_qty']) ?>–<?= number_format($band['max_qty']) ?> · <?= billing_rate_markup($band['unit_price'], isset($band['offer_price']) ? $band['offer_price'] : 0, true) ?><?= !empty($band['is_start']) ? ' · starts from' : '' ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="font-heading font-bold text-white text-xl mb-4">Volume rate</p>
                            <?php endif; ?>
                            <?php
                            $planHref = 'checkout.php?plan=' . rawurlencode($plan['code']);
                            $planLabel = $service['action'];
                            if ($plan['code'] === 'domain-com' || $plan['code'] === 'domain-np') {
                                $planHref = $plan['code'] === 'domain-np' ? '../domain.php' : '../domain.php?tld=com';
                                $planLabel = 'Check this name';
                            }
                            ?>
                            <a href="<?= e($planHref) ?>" class="mt-auto inline-flex justify-center px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition"><?= e($planLabel) ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
