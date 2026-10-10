<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/services-actions.php';
?>
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-1">Services</h1>
        <p class="text-slate-500 text-sm">Change a public price here. The website and the client shop use the saved amount. Checkout adds 13% VAT on top.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<div x-data="{ tab: '<?= e($serviceTab) ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" @click="tab='prices'" :class="tab==='prices' ? 'is-on' : ''">Prices</button>
    <button type="button" @click="tab='photos'" :class="tab==='photos' ? 'is-on' : ''">Photos</button>
    <button type="button" @click="tab='words'" :class="tab==='words' ? 'is-on' : ''">Words</button>
</div>
<div x-show="tab==='prices'">
<div x-data="{ sub: 'sms' }" class="mb-6">
<div class="portal-tabs" role="tablist" aria-label="Price groups">
    <button type="button" role="tab" @click="sub='sms'" :class="sub==='sms' ? 'is-on' : ''" :aria-selected="sub==='sms'">SMS and voice</button>
    <button type="button" role="tab" @click="sub='other'" :class="sub==='other' ? 'is-on' : ''" :aria-selected="sub==='other'">Domains, hosting, email, websites, training</button>
</div>
<div x-show="sub==='sms'">
<section class="dash-panel mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">SMS and voice, price each</h3>
        <p class="text-slate-500 text-xs mt-1">A quantity inside a band uses that band’s rate. Leave Offer blank to charge the regular rate. Mark Starts from on the band the homepage should show. The same rates are under Billing, Rates.</p>
    </div>
    <form method="POST" class="p-5 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach (array('bulk-sms' => array('Bulk SMS', $smsSlabs), 'bulk-voice' => array('Auto voice calls', $voiceSlabs)) as $slug => $pair): ?>
            <?php
            $heading = $pair[0];
            $slabRows = $pair[1];
            $hasStart = false;
            foreach ($slabRows as $slab) {
                if (!empty($slab['is_start'])) {
                    $hasStart = true;
                    break;
                }
            }
            ?>
            <div>
                <h4 class="text-white text-sm font-medium mb-3"><?= e($heading) ?></h4>
                <div class="hidden sm:grid sm:grid-cols-[140px_1fr_1fr_1fr_1fr] gap-3 text-slate-500 text-xs mb-2">
                    <span>Homepage</span><span>From</span><span>To</span><span>Regular NPR each</span><span>Offer NPR each</span>
                </div>
                <div class="space-y-3">
                    <?php foreach ($slabRows as $index => $slab): ?>
                        <?php
                        $slabId = (int) $slab['id'];
                        $checked = !empty($slab['is_start']) || (!$hasStart && $index === 0);
                        $minValue = (string) (int) $slab['min_qty'];
                        $maxValue = (string) (int) $slab['max_qty'];
                        $rateValue = billing_money($slab['unit_price']);
                        $offerValue = !empty($slab['offer_price']) && (float) $slab['offer_price'] > 0 ? billing_money($slab['offer_price']) : '';
                        if ($err !== '' && isset($_POST['slab'][$slabId]) && is_array($_POST['slab'][$slabId])) {
                            $postedSlab = $_POST['slab'][$slabId];
                            $minValue = isset($postedSlab['min']) ? (string) $postedSlab['min'] : $minValue;
                            $maxValue = isset($postedSlab['max']) ? (string) $postedSlab['max'] : $maxValue;
                            $rateValue = isset($postedSlab['price']) ? (string) $postedSlab['price'] : $rateValue;
                            $offerValue = isset($postedSlab['offer']) ? (string) $postedSlab['offer'] : $offerValue;
                            $checked = isset($_POST['start'][$slug]) && (string) $_POST['start'][$slug] === (string) $slabId;
                        }
                        $shown = billing_selling_price($rateValue, $offerValue);
                        ?>
                        <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr_1fr_1fr_1fr] gap-3 items-center">
                            <label class="flex items-center gap-2 text-slate-300 text-sm">
                                <input type="radio" name="start[<?= e($slug) ?>]" value="<?= $slabId ?>" <?= $checked ? 'checked' : '' ?>>
                                Starts from
                            </label>
                            <input name="slab[<?= $slabId ?>][min]" value="<?= e($minValue) ?>" class="form-input" inputmode="numeric" aria-label="From quantity">
                            <input name="slab[<?= $slabId ?>][max]" value="<?= e($maxValue) ?>" class="form-input" inputmode="numeric" aria-label="To quantity">
                            <input name="slab[<?= $slabId ?>][price]" value="<?= e($rateValue) ?>" class="form-input" inputmode="decimal" aria-label="Regular rate each">
                            <input name="slab[<?= $slabId ?>][offer]" value="<?= e($offerValue) ?>" class="form-input" inputmode="decimal" placeholder="No offer" aria-label="Offer rate each">
                        </div>
                        <p class="text-slate-500 text-xs -mt-1">Public rate <?= e(billing_unit_label($shown)) ?> each, before VAT.</p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="save_service_slabs" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save SMS and voice rates</button>
    </form>
</section>
</div>
<div x-show="sub==='other'" x-cloak>
<section class="dash-panel mb-6">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white">Domain, hosting, email, website, and training</h3>
        <p class="text-slate-500 text-xs mt-1">Type the regular price in NPR. Leave Offer blank when there is no special rate. A lower offer is what visitors pay, and the regular price is crossed out. Clear the offer to go back.</p>
    </div>
    <form method="POST" class="p-5 space-y-8">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php foreach ($posterServices as $slug => $service): ?>
            <?php if ($slug === 'bulk-sms' || $slug === 'bulk-voice' || empty($plansByService[$slug])) { continue; } ?>
            <div>
                <h4 class="text-white text-sm font-medium mb-3"><?= e($service['title']) ?></h4>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($plansByService[$slug] as $plan): ?>
                        <?php
                        $code = (string) $plan['code'];
                        $regular = billing_money($plan['price']);
                        $offer = !empty($plan['offer_price']) && (float) $plan['offer_price'] > 0 ? billing_money($plan['offer_price']) : '';
                        if ($err !== '' && isset($_POST['price'][$code])) {
                            $regular = (string) $_POST['price'][$code];
                            $offer = isset($_POST['offer'][$code]) ? (string) $_POST['offer'][$code] : $offer;
                        }
                        $shown = billing_selling_price($regular, $offer);
                        ?>
                        <div class="rounded-2xl border border-slate-800 p-4">
                            <p class="text-white text-sm font-medium"><?= e($plan['name']) ?></p>
                            <p class="text-slate-500 text-xs mt-1 mb-3"><?= e(billing_cycle_label($plan['billing_cycle'])) ?> · visitors pay <?= e(billing_money_label($shown)) ?> before VAT</p>
                            <label class="block text-slate-500 text-xs mb-1" for="price-<?= e($code) ?>">Regular price (NPR)</label>
                            <input id="price-<?= e($code) ?>" name="price[<?= e($code) ?>]" value="<?= e($regular) ?>" class="form-input" inputmode="decimal">
                            <label class="block text-slate-500 text-xs mt-3 mb-1" for="offer-<?= e($code) ?>">Offer price (NPR)</label>
                            <input id="offer-<?= e($code) ?>" name="offer[<?= e($code) ?>]" value="<?= e($offer) ?>" class="form-input" inputmode="decimal" placeholder="Blank when there is no offer">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" name="save_service_prices" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save service prices</button>
    </form>
</section>
</div>
</div>
</div>
<div x-show="tab==='photos'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">See rates photos</h3></div>
    <div class="p-5 space-y-5">
        <p class="text-slate-500 text-sm">Upload a photo or poster for a service. It appears at the top of that service page. Leave it empty and nothing is shown.</p>
        <?php foreach ($posterServices as $slug => $service): ?>
            <?php $posterPreview = site_service_poster($conn, $slug); ?>
            <form method="POST" action="" enctype="multipart/form-data" class="grid sm:grid-cols-[140px_1fr] gap-4 items-center border-t border-slate-800 pt-5 first:border-0 first:pt-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="poster_slug" value="<?= e($slug) ?>">
                <div>
                    <?php if ($posterPreview !== ''): ?>
                        <img src="<?= e($posterPreview) ?>" alt="" class="w-full max-h-24 object-contain bg-white rounded-xl p-1">
                    <?php else: ?>
                        <div class="h-20 rounded-xl border border-dashed border-slate-700 text-slate-500 text-xs flex items-center justify-center">No photo</div>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-white text-sm font-medium mb-2"><?= e($service['title']) ?></p>
                    <input type="file" name="poster" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                    <?php if ($posterPreview !== ''): ?>
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="remove_poster" value="1"> Remove the current photo
                        </label>
                    <?php endif; ?>
                    <button type="submit" name="save_poster" class="mt-3 px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save photo</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</div>
</div>
<div x-show="tab==='words'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Public service text</h3></div>
    <div class="p-5 space-y-6">
        <p class="text-slate-500 text-sm">The title, summary, and tags are the homepage card. The kicker, opening line, and points are the public service page. One point per line. Leave a page field blank to keep the prepared text.</p>
        <?php foreach ($posterServices as $slug => $service): ?>
            <?php
            $view = billing_saved_service_view($conn, $slug, $serviceOverrides);
            $pageCopy = billing_public_page($conn, $slug);
            $guides = billing_service_guide();
            $guideNotes = isset($guides[$slug]['notes']) ? $guides[$slug]['notes'] : array();
            $pointText = !empty($pageCopy['points_saved']) ? implode("\n", $pageCopy['points']) : implode("\n", $guideNotes);
            ?>
            <form method="POST" action="" class="space-y-3 border-t border-slate-800 pt-5 first:border-0 first:pt-0">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="service_slug" value="<?= e($slug) ?>">
                <p class="text-white text-sm font-medium"><?= e($view['title']) ?></p>
                <label class="block text-slate-500 text-xs">Card title</label>
                <input type="text" name="title" maxlength="120" required class="form-input" value="<?= e($view['title']) ?>">
                <label class="block text-slate-500 text-xs">Card summary</label>
                <textarea name="description" maxlength="500" rows="3" class="form-input"><?= e($view['summary']) ?></textarea>
                <label class="block text-slate-500 text-xs">Tags, separated by commas</label>
                <input type="text" name="features" maxlength="300" class="form-input" value="<?= e(implode(', ', $view['tags'])) ?>">
                <label class="block text-slate-500 text-xs">Page kicker</label>
                <input type="text" name="kicker" maxlength="160" class="form-input" value="<?= e($pageCopy['kicker']) ?>">
                <label class="block text-slate-500 text-xs">Opening line on the service page</label>
                <textarea name="lead" maxlength="600" rows="3" class="form-input"><?= e($pageCopy['lead']) ?></textarea>
                <label class="block text-slate-500 text-xs">Page points, one per line</label>
                <textarea name="points" maxlength="2000" rows="6" class="form-input"><?= e($pointText) ?></textarea>
                <button type="submit" name="save_public_service" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save text</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
