<?php
require_once __DIR__ . '/../config.php';

$requestedPlan = '';
if (isset($_POST['plan']) && is_string($_POST['plan'])) {
    $requestedPlan = $_POST['plan'];
} elseif (isset($_GET['plan']) && is_string($_GET['plan'])) {
    $requestedPlan = $_GET['plan'];
}

if (!preg_match('/^[a-z0-9-]{2,40}$/', $requestedPlan)) {
    header('Location: shop.php');
    exit;
}
if ($requestedPlan === 'domain-com' || $requestedPlan === 'domain-np') {
    header('Location: ' . ($requestedPlan === 'domain-np' ? '../domain.php' : '../domain.php?tld=com'));
    exit;
}

if (!is_client_logged_in()) {
    $_SESSION['client_next'] = client_safe_next('checkout.php?plan=' . $requestedPlan);
    header('Location: login.php');
    exit;
}
if (!auth_account_is_active('client')) {
    auth_drop_role('client');
    flash('login_error', 'Your account is suspended. Contact support.');
    header('Location: login.php');
    exit;
}

$plan = billing_find_plan($conn, $requestedPlan);
if (!$plan) {
    header('Location: shop.php');
    exit;
}

$services = billing_service_definitions();
$service = isset($services[$plan['service_slug']]) ? $services[$plan['service_slug']] : array('title' => 'Service', 'summary' => '', 'action' => 'Continue');
$needs = (string) $plan['needs_detail'];
$error = '';
$preview = null;
$values = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['review_order']) || isset($_POST['confirm_purchase']))) {
    verify_csrf();
    $orderGuard = 'order-submit-' . $plan['code'];
    $orderExpected = isset($_SESSION['form_guard'][$orderGuard]['token']) ? (string) $_SESSION['form_guard'][$orderGuard]['token'] : '';
    $orderPosted = isset($_POST['order_token']) && is_string($_POST['order_token']) ? $_POST['order_token'] : '';
    $orderFresh = $orderExpected !== '' && hash_equals($orderExpected, $orderPosted);
    $preview = billing_prepare_order($conn, $plan, $_POST);
    if (empty($preview['ok'])) {
        $error = $preview['error'];
        $preview = null;
    } elseif (isset($_POST['confirm_purchase']) && !$orderFresh) {
        // The same page was sent twice (double tap, Back button, reload). Never charge a second time.
        $error = 'This order was already sent. Open My Services to see it before you try again.';
        $preview = null;
    } elseif (isset($_POST['confirm_purchase'])) {
        unset($_SESSION['form_guard'][$orderGuard]);
        $result = billing_purchase($conn, (int) get_client_id(), $plan, $_POST);
        if (!empty($result['ok'])) {
            if ($needs === 'sms') {
                $done = 'Credit is on your account. Send it from the SMS dashboard on the next page. An API token can be created there after identity is approved.';
                flash('billing', $done);
                header('Location: sms-portal.php');
            } elseif ($needs === 'voice') {
                $done = 'Voice credits are on your account. Save the script under Messages. The team places the call, and the credits are used then.';
                flash('billing', $done);
                header('Location: campaigns.php');
            } else {
                $done = (isset($result['status']) && $result['status'] === 'booked')
                    ? 'Booking received. The details you entered are saved, so the work can start without another call.'
                    : 'Payment complete. The details you entered are saved on this service.';
                flash('billing', $done);
                header('Location: services.php');
            }
            exit;
        }
        $error = isset($result['error']) ? $result['error'] : 'The order could not be completed.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$balance = billing_balance($conn, (int) get_client_id());
$catalogNet = billing_selling_price($plan['price'], isset($plan['offer_price']) ? $plan['offer_price'] : 0);
$catalogBill = billing_vat_bill($catalogNet);
if ($preview && !empty($preview['ok'])) {
    $billNet = (float) $preview['net'];
    $billVat = (float) $preview['vat'];
    $billTotal = (float) $preview['price'];
    $due = $billTotal;
} elseif ($needs === 'sms' || $needs === 'voice') {
    $billNet = 0;
    $billVat = 0;
    $billTotal = 0;
    $due = 0;
} else {
    $billNet = $catalogBill['net'];
    $billVat = $catalogBill['vat'];
    $billTotal = $catalogBill['total'];
    $due = $billTotal;
}
$short = $due > 0 && ($balance + 0.001 < $due);
require_once __DIR__ . '/../includes/shop-view.php';
$nextStepsByKind = array(
    'sms' => array('The SMS credits are added to your account the moment you pay.', 'You send from the SMS page. Sending more than 100 needs approved identity.', 'Credits for messages that cannot be sent are returned.'),
    'voice' => array('The voice credits are added when you pay.', 'Save your script under Messages. Our team places the calls.', 'Credits are used only when calls are made.'),
    'domain' => array('We register the name for you and mark it active here.', 'You can follow it in My Services.'),
    'hosting' => array('We set up the server and send you the access details.', 'You can follow it in My Services.'),
    'email' => array('We create the mailboxes and send you the login details.', 'You can follow it in My Services.'),
    'website' => array('Your booking is saved with every answer on this page.', 'The team contacts you to start. You will not need to explain it again.'),
    'training' => array('Your request is saved with the date, place and topics.', 'We confirm the trainer and the time with you.')
);
$nextSteps = isset($nextStepsByKind[$needs]) ? $nextStepsByKind[$needs] : array('We set it up and you can follow it in My Services.');
$slabs = ($needs === 'sms' || $needs === 'voice') ? billing_slabs_for($conn, $plan['service_slug']) : array();

if (!function_exists('checkout_value')) {
    function checkout_value($values, $key)
    {
        return isset($values[$key]) && is_string($values[$key]) ? $values[$key] : '';
    }
}
?>
<?php
$twoStep = $needs !== 'sms' && $needs !== 'voice';
$stageNow = $twoStep ? 1 : ($preview && !empty($preview['ok']) ? 2 : 1);
$stepNames = $twoStep ? array('Your details', 'Pay from wallet') : array('Your details', 'Check the price', 'Pay from wallet');
?>
<div class="co-head">
    <a href="shop.php" class="co-back">&larr; All services</a>
    <h1 class="co-title"><?= e($service['action']) ?></h1>
    <p class="co-sub"><?= e($service['title']) ?> · <?= e($plan['name']) ?> · <a href="../service.php?slug=<?= e(rawurlencode($plan['service_slug'])) ?>">What is included</a></p>
    <ol class="co-steps" aria-label="Steps">
        <?php foreach ($stepNames as $n => $name): ?>
            <li class="<?= $n + 1 < $stageNow ? 'is-done' : ($n + 1 === $stageNow ? 'is-now' : '') ?>"<?= $n + 1 === $stageNow ? ' aria-current="step"' : '' ?>><b><?= $n + 1 ?></b><?= e($name) ?></li>
        <?php endforeach; ?>
    </ol>
</div>

<div class="co-layout">
    <section class="co-main">
        <div class="co-card">
            <p class="co-lead"><?= e($plan['summary']) ?></p>
            <?php if ($error !== ''): ?>
                <div class="co-alert is-bad" role="alert"><p><?= e($error) ?></p></div>
            <?php endif; ?>
            <?php if ($preview && !empty($preview['ok'])): ?>
                <div class="co-bill">
                    <p class="co-bill-title">Your bill</p>
                    <p class="text-slate-300">List price <?= e(billing_money_label($preview['net'])) ?></p>
                    <p class="text-slate-300">VAT 13% <?= e(billing_money_label($preview['vat'])) ?></p>
                    <p class="text-white font-medium">Total <?= e(billing_money_label($preview['price'])) ?></p>
                    <?php foreach ($preview['brief'] as $label => $value): ?>
                        <p class="text-slate-300 max-h-24 overflow-auto whitespace-pre-wrap"><span class="text-slate-500"><?= e($label) ?>:</span> <?= e($value) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($short): ?>
                <?php $checkoutMethods = billing_payment_methods($conn); ?>
                <div class="co-alert" role="alert">
                    <p><b>Your wallet is NPR <?= e(number_format(max(0, $due - $balance), 2)) ?> short.</b> It has <?= e(billing_money_label($balance)) ?> and this order is <?= e(billing_money_label($due)) ?>.</p>
                    <p>Add at least that amount, then come back to this page and pay. What you typed below stays here.</p>
                    <p><a class="co-alert-btn" href="wallet.php">Add funds to the wallet</a></p>
                    <?php if ($checkoutMethods): ?>
                        <ul class="co-methods">
                        <?php foreach ($checkoutMethods as $checkoutMethod): ?>
                            <li><b><?= e($checkoutMethod['label']) ?></b> <span><?= e($checkoutMethod['detail']) ?></span></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="checkout.php?plan=<?= e(rawurlencode($plan['code'])) ?>" class="co-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="plan" value="<?= e($plan['code']) ?>">
                <input type="hidden" name="order_token" value="<?= e(billing_form_guard_token('order-submit-' . $plan['code'])) ?>">

                <?php if ($needs === 'sms' || $needs === 'voice'): ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="quantity"><?= $needs === 'sms' ? 'How many SMS' : 'How many calls' ?></label>
                        <input id="quantity" name="quantity" inputmode="numeric" required value="<?= e(checkout_value($values, 'quantity')) ?>" class="form-input" placeholder="<?= $needs === 'sms' ? '5000' : '1000' ?>">
                    
                        <p class="co-estimate" id="co-estimate" aria-live="polite"></p>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="audience">Who is this for?</label>
                            <select id="audience" name="audience" class="form-input" required>
                                <option value="">Choose</option>
                                <?php foreach (billing_audiences() as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= checkout_value($values, 'audience') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="purpose">Why is it being sent?</label>
                            <select id="purpose" name="purpose" class="form-input" required>
                                <option value="">Choose</option>
                                <?php foreach (billing_purposes() as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= checkout_value($values, 'purpose') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($needs === 'sms'): ?>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="sender_id">Sender name</label>
                            <input id="sender_id" name="sender_id" required maxlength="11" value="<?= e(checkout_value($values, 'sender_id')) ?>" class="form-input" placeholder="Sahakari">
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="language">Language</label>
                            <select id="language" name="language" class="form-input" required>
                                <?php foreach (array('Nepali', 'English') as $language): ?>
                                    <option value="<?= e($language) ?>" <?= checkout_value($values, 'language') === $language ? 'selected' : '' ?>><?= e($language) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="message"><?= $needs === 'sms' ? 'Message' : 'Voice script' ?></label>
                        <textarea id="message" name="message" required rows="4" class="form-input" placeholder="<?= $needs === 'sms' ? 'The exact text to send' : 'The exact words to speak' ?>"><?= e(checkout_value($values, 'message')) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="schedule_date">Send date, if you already know it</label>
                        <input id="schedule_date" type="date" name="schedule_date" value="<?= e(checkout_value($values, 'schedule_date')) ?>" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="numbers">Number list, optional</label>
                        <textarea id="numbers" name="numbers" rows="4" class="form-input" placeholder="One mobile number per line. You can also add the full list from Messages after payment."><?= e(checkout_value($values, 'numbers')) ?></textarea>
                    </div>
                    <?php
                    $guardKey = 'order-' . $plan['code'];
                    $declarationAccepted = checkout_value($values, 'legal_accept') === '1';
                    require __DIR__ . '/../includes/use-declaration.php';
                    ?>
                <?php elseif ($needs === 'domain' || $needs === 'hosting' || $needs === 'email'): ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="domain">Domain name</label>
                        <input id="domain" name="domain" required value="<?= e(checkout_value($values, 'domain')) ?>" class="form-input" placeholder="yourcoop.com.np">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="organization">Organization or person</label>
                        <input id="organization" name="organization" required maxlength="120" value="<?= e(checkout_value($values, 'organization')) ?>" class="form-input">
                    </div>
                    <?php if ($needs === 'hosting'): ?>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="server_use">What should this server host?</label>
                            <select id="server_use" name="server_use" class="form-input" required>
                                <option value="">Choose</option>
                                <?php foreach (array('New website', 'Existing website', 'Website and email') as $use): ?>
                                    <option value="<?= e($use) ?>" <?= checkout_value($values, 'server_use') === $use ? 'selected' : '' ?>><?= e($use) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($needs === 'email'): ?>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="mailboxes">Mailbox names, one per line</label>
                            <textarea id="mailboxes" name="mailboxes" required rows="<?= max(3, (int) $plan['unit_quantity']) ?>" class="form-input" placeholder="info"><?= e(checkout_value($values, 'mailboxes')) ?></textarea>
                            <p class="text-slate-500 text-xs mt-1">Enter exactly <?= (int) $plan['unit_quantity'] ?> name<?= (int) $plan['unit_quantity'] === 1 ? '' : 's' ?>, without @. Example: info</p>
                        </div>
                    <?php endif; ?>
                <?php elseif ($needs === 'website'): ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="organization">Organization or person</label>
                        <input id="organization" name="organization" required maxlength="120" value="<?= e(checkout_value($values, 'organization')) ?>" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="goal">What must the website do?</label>
                        <textarea id="goal" name="goal" required rows="4" class="form-input" placeholder="Who it is for, which services to show, and what a visitor should do next."><?= e(checkout_value($values, 'goal')) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="about">About text to publish</label>
                        <textarea id="about" name="about" required rows="4" class="form-input" placeholder="The short introduction visitors should read."><?= e(checkout_value($values, 'about')) ?></textarea>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="public_phone">Phone on the website</label>
                            <input id="public_phone" name="public_phone" required maxlength="30" value="<?= e(checkout_value($values, 'public_phone')) ?>" class="form-input" placeholder="+977 ...">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="public_email">Email on the website</label>
                            <input id="public_email" type="email" name="public_email" required maxlength="120" value="<?= e(checkout_value($values, 'public_email')) ?>" class="form-input" placeholder="info@yourdomain.com">
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="domain">Preferred domain, if you have one</label>
                            <input id="domain" name="domain" value="<?= e(checkout_value($values, 'domain')) ?>" class="form-input" placeholder="yourname.com">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="deadline">Deadline</label>
                            <input id="deadline" type="date" name="deadline" required value="<?= e(checkout_value($values, 'deadline')) ?>" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="address">Business address</label>
                        <input id="address" name="address" required maxlength="180" value="<?= e(checkout_value($values, 'address')) ?>" class="form-input" placeholder="Area, municipality, district">
                    </div>
                <?php elseif ($needs === 'training'): ?>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="organization">Cooperative or organization</label>
                        <input id="organization" name="organization" required maxlength="120" value="<?= e(checkout_value($values, 'organization')) ?>" class="form-input">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="headcount">How many people? Up to <?= (int) $plan['unit_quantity'] ?></label>
                            <input id="headcount" name="headcount" inputmode="numeric" required value="<?= e(checkout_value($values, 'headcount')) ?>" class="form-input">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="preferred_date">Preferred date</label>
                            <input id="preferred_date" type="date" name="preferred_date" required value="<?= e(checkout_value($values, 'preferred_date')) ?>" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="address">Venue address</label>
                        <input id="address" name="address" required maxlength="180" value="<?= e(checkout_value($values, 'address')) ?>" class="form-input" placeholder="Where the trainer should come">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="district">District</label>
                        <input id="district" name="district" required maxlength="80" value="<?= e(checkout_value($values, 'district')) ?>" class="form-input">
                    </div>
                    <fieldset>
                        <legend class="block text-slate-400 text-xs font-medium mb-2">Topics to cover</legend>
                        <div class="space-y-2">
                            <?php
                            $selectedTopics = isset($values['topics']) && is_array($values['topics']) ? $values['topics'] : array();
                            foreach (billing_training_topics() as $topicKey => $topicLabel):
                            ?>
                                <label class="flex items-start gap-2 text-sm text-slate-300">
                                    <input type="checkbox" name="topics[]" value="<?= e($topicKey) ?>" <?= in_array($topicKey, $selectedTopics, true) ? 'checked' : '' ?>>
                                    <span><?= e($topicLabel) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note">Anything the trainer should know</label>
                        <textarea id="note" name="note" rows="3" class="form-input" placeholder="Language mix, room size, or a topic to spend more time on."><?= e(checkout_value($values, 'note')) ?></textarea>
                    </div>
                <?php endif; ?>

                <div class="co-actions">
                <?php if ($needs !== 'sms' && $needs !== 'voice' && !$short): ?>
                    <button type="submit" name="confirm_purchase" value="1" class="co-pay">
                        Pay <?= e(billing_money_label($due)) ?> from wallet
                    </button>
                <?php elseif ($preview && !empty($preview['ok']) && !$short): ?>
                    <button type="submit" name="confirm_purchase" value="1" class="co-pay">
                        Pay <?= e(billing_money_label($preview['price'])) ?> from wallet
                    </button>
                <?php elseif ($needs !== 'sms' && $needs !== 'voice' && $short): ?>
                    <p class="text-slate-400 text-sm">The bill is already on this page. Add the wallet funds, then come back and pay.</p>
                <?php else: ?>
                    <button type="submit" name="review_order" value="1" class="co-pay">
                        Review price
                    </button>
                <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
    <aside class="co-side" aria-label="Order summary">
        <div class="co-card co-summary">
            <h2>Order summary</h2>
            <p class="co-plan"><b><?= e($plan['name']) ?></b><span><?= e(shop_cycle_chip($plan['billing_cycle'], (int) $plan['auto_renew_default'] === 1)) ?></span></p>
            <?php if ($slabs): ?>
                <table class="shop-ladder" id="co-ladder"><caption class="sr-only">Rate by quantity</caption><thead><tr><th scope="col">Quantity</th><th scope="col">Each</th></tr></thead><tbody>
                <?php foreach ($slabs as $slab): ?>
                    <tr><th scope="row"><?= number_format($slab['min_qty']) ?> to <?= number_format($slab['max_qty']) ?></th><td><?= billing_rate_markup($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0, true, '') ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
            <dl class="co-lines">
                <?php if ($billTotal > 0): ?>
                    <div><dt>List price</dt><dd><?= e(billing_money_label($billNet)) ?></dd></div>
                    <div><dt>VAT 13%</dt><dd><?= e(billing_money_label($billVat)) ?></dd></div>
                    <div class="is-total"><dt>You pay</dt><dd><?= e(billing_money_label($billTotal)) ?></dd></div>
                <?php else: ?>
                    <div class="is-total"><dt>You pay</dt><dd id="co-total">Set the quantity</dd></div>
                    <p class="co-fine">List price for your quantity plus 13% VAT. The exact bill is shown on the next step.</p>
                <?php endif; ?>
                <div><dt>Wallet now</dt><dd><?= e(billing_money_label($balance)) ?></dd></div>
                <?php if ($billTotal > 0): ?>
                    <div class="<?= $short ? 'is-short' : 'is-ok' ?>"><dt><?= $short ? 'Still needed' : 'Wallet after paying' ?></dt><dd><?= e($short ? billing_money_label($due - $balance) : billing_money_label($balance - $due)) ?></dd></div>
                <?php endif; ?>
            </dl>
            <h3>What happens next</h3>
            <ul class="co-next">
                <?php foreach ($nextSteps as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
            </ul>
            <p class="co-fine"><?= (int) $plan['auto_renew_default'] === 1 && $plan['billing_cycle'] !== 'one_time' ? 'This renews on its own from your wallet at the same bill, VAT included. You can turn that off in My Services.' : 'No automatic renewal.' ?> Paying is only from your wallet; nothing is charged until you press the Pay button.</p>
        </div>
    </aside>
</div>
<?php if ($slabs): ?>
<script type="application/json" id="co-slabs"><?= json_encode(array_map(function ($slab) { return array('min' => (int) $slab['min_qty'], 'max' => (int) $slab['max_qty'], 'unit' => billing_selling_price($slab['unit_price'], isset($slab['offer_price']) ? $slab['offer_price'] : 0)); }, $slabs), JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script defer src="../assets/js/checkout.js"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
