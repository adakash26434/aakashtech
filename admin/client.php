<?php
ob_start();
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/client-actions.php';
?>
<div class="mb-8">
    <a href="<?= e($back) ?>" class="text-brand-400 text-sm">← Clients</a>
    <h1 class="font-heading font-bold text-white text-2xl mt-2"><?= $client ? e($client['name']) : 'Client' ?></h1>
</div>

<?php if ($notice !== ''): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($problem !== ''): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($problem) ?></div>
<?php endif; ?>

<?php if (!$client): ?>
    <div class="dash-panel"><p class="p-8 text-slate-500 text-sm">That client was not found.</p></div>
<?php else: ?>
    <p class="text-slate-400 text-sm mb-4">Account is the person. Wallet is NPR. SMS is texts, and a top-up can be taken back when the money did not arrive and the texts were not sent. Services are domain, hosting, email, a website, training, or voice calls.</p>
    <div x-data="{ tab: '<?= e($clientTab) ?>' }">
    <div class="portal-tabs" role="tablist">
        <button type="button" @click="tab='account'" :class="tab==='account' ? 'is-on' : ''">Account</button>
        <button type="button" @click="tab='wallet'" :class="tab==='wallet' ? 'is-on' : ''">Wallet · NPR <?= e(number_format((float) $balance, 2)) ?></button>
        <button type="button" @click="tab='sms'" :class="tab==='sms' ? 'is-on' : ''">SMS · <?= number_format((int) $units['sms']) ?> left</button>
        <button type="button" @click="tab='services'" :class="tab==='services' ? 'is-on' : ''">Services · <?= count($services) ?></button>
    </div>
    <div x-show="tab==='account'">
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Account</h2></div>
            <dl class="p-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="text-white text-right"><?= e($client['email']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Mobile</dt><dd class="text-white text-right"><?= e($client['phone']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Company</dt><dd class="text-white text-right"><?= e(trim((string) $client['company']) !== '' ? $client['company'] : '—') ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Address</dt><dd class="text-white text-right"><?= e(trim((string) $client['address']) !== '' ? $client['address'] : '—') ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd class="text-white text-right"><?= e(ucfirst((string) $client['status'])) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Wallet</dt><dd class="text-white text-right">NPR <?= e(number_format((float) $balance, 2)) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">SMS used / left</dt><dd class="text-white text-right"><?= number_format((int) $figures['used']) ?> / <?= number_format((int) $figures['left']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Joined</dt><dd class="text-white text-right"><?= e(date('M j, Y', strtotime($client['created_at']))) ?></dd></div>
            </dl>
            <div class="px-5 pb-5 flex flex-wrap gap-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                    <button type="submit" name="toggle_client"<?= $client['status'] === 'active' ? ' onclick="return confirm(\'Suspend this client? They cannot sign in until you activate them again.\')"' : '' ?> class="text-sm bg-transparent border-0 cursor-pointer p-0 <?= $client['status'] === 'active' ? 'text-red-400' : 'text-green-400' ?>"><?= $client['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                </form>
                <?php if (trim((string) $client['totp_secret']) !== ''): ?>
                    <form method="POST" onsubmit="return confirm('Reset Google Authenticator for this client?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                        <button type="submit" name="reset_authenticator" class="text-sm bg-transparent border-0 cursor-pointer p-0 text-slate-400">Reset authenticator</button>
                    </form>
                <?php endif; ?>
                <a class="text-brand-400 text-sm" href="sms-line.php?tab=history&client=<?= (int) $client['id'] ?>">SMS history</a>
            </div>
        </section>
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Change email or mobile</h2></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-slate-500 text-xs">The client cannot change these. Both stay required.</p>
                <input name="contact_email" type="email" required maxlength="120" class="form-input" value="<?= e($contactEmail) ?>">
                <input name="contact_phone" required inputmode="numeric" maxlength="16" class="form-input" value="<?= e($contactPhone) ?>">
                <button type="submit" name="set_contact" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save email and mobile</button>
            </form>
        </section>
    </div>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Open this portal</h2></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-slate-400 text-sm">Look at the client portal without their password or authenticator code. A bar at the top shows this is the office view. Leave it to come back here.</p>
                <button type="submit" name="open_portal" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Open client portal</button>
            </form>
        </section>
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Password</h2></div>
            <div class="p-5 space-y-4">
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                    <p class="text-slate-400 text-sm">Email a link when they still have the inbox. They choose the new password. The link works for 30 minutes.</p>
                    <button type="submit" name="email_reset" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-xl">Email a reset link</button>
                </form>
                <form method="POST" class="space-y-3 border-t border-slate-800 pt-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                    <p class="text-slate-400 text-sm">Set a password when they are at the office. It is shown once on this page so you can tell them. The email says the password changed and does not include it.</p>
                    <input name="new_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" class="form-input" placeholder="New password, at least 8 characters">
                    <input name="new_password_again" type="password" required minlength="8" maxlength="72" autocomplete="new-password" class="form-input" placeholder="Type it again">
                    <button type="submit" name="set_password" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Save password</button>
                </form>
            </div>
        </section>
    </div>
    </div>
    <div x-show="tab==='wallet'" x-cloak>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Add money</h2></div>
            <form method="POST" class="p-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-white text-2xl font-heading font-bold">NPR <?= e(number_format((float) $balance, 2)) ?> <span class="text-slate-400 text-sm font-medium">in the wallet</span></p>
                <p class="text-slate-400 text-sm">Use this when cash or a transfer arrived. The client spends it on domain, hosting, email, and renewals. This does not add SMS.</p>
                <label class="block text-slate-400 text-xs font-medium" for="wallet_amount">Amount (NPR)</label>
                <input id="wallet_amount" name="wallet_amount" type="number" min="1" max="1000000" required class="form-input" placeholder="5000">
                <label class="block text-slate-400 text-xs font-medium" for="wallet_note">Where it was received</label>
                <input id="wallet_note" name="wallet_note" type="text" maxlength="160" required class="form-input" placeholder="Cash at the office">
                <button type="submit" name="add_wallet" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add to wallet</button>
            </form>
        </section>
        <section class="dash-panel overflow-hidden">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Wallet activity</h2></div>
            <?php if ($walletRows): ?>
                <div class="divide-y divide-slate-800">
                    <?php foreach ($walletRows as $walletRow): ?>
                        <?php
                        $canTakeWallet = $walletRow['kind'] === 'topup' && $walletRow['method'] === 'office' && $walletRow['status'] === 'completed' && $walletRow['direction'] === 'credit';
                        $walletLabel = $walletRow['kind'] === 'topup' ? 'Payment in' : ($walletRow['kind'] === 'purchase' ? 'Spent' : ($walletRow['kind'] === 'refund' ? 'Returned' : ($walletRow['kind'] === 'renewal' ? 'Renewal' : ucfirst((string) $walletRow['kind']))));
                        if ($walletRow['status'] === 'reversed') {
                            $walletLabel = 'Taken back';
                        } elseif ($walletRow['status'] === 'pending') {
                            $walletLabel = 'Waiting';
                        } elseif ($walletRow['status'] === 'rejected') {
                            $walletLabel = 'Rejected';
                        }
                        ?>
                        <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-white text-sm"><?= e($walletLabel) ?> · NPR <?= e(number_format((float) $walletRow['amount'], 2)) ?></p>
                                <p class="text-slate-500 text-xs"><?= e($walletRow['reference_note']) ?> · <?= e(date('M j, Y', strtotime($walletRow['created_at']))) ?></p>
                            </div>
                            <?php if ($canTakeWallet): ?>
                                <form method="POST" onsubmit="return confirm('Take this payment back? It leaves the wallet only if the client has not spent it.');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                    <input type="hidden" name="entry_id" value="<?= (int) $walletRow['id'] ?>">
                                    <button type="submit" name="reverse_wallet" class="text-yellow-300 text-sm bg-transparent border-0 cursor-pointer p-0">Take back</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="p-5 text-slate-500 text-sm">No wallet activity yet.</p>
            <?php endif; ?>
        </section>
    </div>
    </div>
    <div x-show="tab==='sms'" x-cloak>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Add SMS</h2></div>
            <form method="POST" class="p-5 space-y-3" x-data="{ credits: '' }">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-white text-2xl font-heading font-bold"><?= number_format((int) $units['sms']) ?> <span class="text-slate-400 text-sm font-medium">SMS left on this account</span></p>
                <p class="text-slate-500 text-xs">Used <?= number_format((int) $figures['used']) ?>. Voice calls left: <?= number_format((int) $units['voice_calls']) ?>.<?php if ($bulkLeft !== null): ?> The bulk line you buy from still has <?= number_format((int) $bulkLeft) ?> SMS.<?php endif; ?></p>
                <p class="text-slate-400 text-sm">This adds texts, not wallet money. If the payment never arrives, use Take back on that row. Texts they already sent cannot come back. Until identity is approved, the client can send only 100 SMS in total.</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array(500, 1000, 2000, 5000, 10000) as $quick): ?>
                        <button type="button" class="px-3 py-1.5 rounded-full border border-slate-700 text-sm text-slate-300 hover:text-white" @click="credits='<?= (int) $quick ?>'"><?= number_format($quick) ?></button>
                    <?php endforeach; ?>
                </div>
                <label class="block text-slate-400 text-xs font-medium" for="grant_credits">SMS to add</label>
                <input id="grant_credits" name="grant_credits" type="number" min="1" max="500000" required class="form-input" placeholder="1000" x-model="credits">
                <label class="block text-slate-400 text-xs font-medium" for="grant_note">Why</label>
                <input id="grant_note" name="grant_note" type="text" maxlength="160" required class="form-input" placeholder="Cash at the office, or not paid yet">
                <button type="submit" name="grant_sms" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add SMS</button>
            </form>
        </section>
        <section class="dash-panel overflow-hidden">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">SMS added</h2></div>
            <?php if ($creditNotes): ?>
                <div class="divide-y divide-slate-800">
                    <?php foreach ($creditNotes as $noteRow): ?>
                        <?php $takenBack = trim((string) $noteRow['reversed_at']) !== ''; ?>
                        <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-white text-sm"><?= number_format((int) $noteRow['credits']) ?> SMS<?= $takenBack ? ' · Taken back' : '' ?></p>
                                <p class="text-slate-500 text-xs"><?= e($noteRow['note']) ?> · <?= e(sms_format_time($noteRow['created_at'])) ?></p>
                            </div>
                            <?php if (!$takenBack && (int) $noteRow['credits'] > 0): ?>
                                <form method="POST" onsubmit="return confirm('Take these SMS back? Only texts that were not sent can leave the account.');">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                    <input type="hidden" name="note_id" value="<?= (int) $noteRow['id'] ?>">
                                    <button type="submit" name="reverse_sms" class="text-yellow-300 text-sm bg-transparent border-0 cursor-pointer p-0">Take back</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="p-5 text-slate-500 text-sm">No SMS has been added on this account yet.</p>
            <?php endif; ?>
        </section>
    </div>
    </div>
    <div x-show="tab==='services'" x-cloak>
    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <section class="dash-panel">
            <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Add a service</h2></div>
            <form method="POST" class="p-5 space-y-3" x-data='<?= json_encode(array('plan' => '', 'needs' => $planNeeds), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>'>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                <p class="text-slate-400 text-sm">Use this for domain, hosting, email, a website, training, or voice calls. The wallet is not charged now. If the payment never arrives, Take back closes it and stops renewal. Voice calls that were already placed stay used.</p>
                <label class="block text-slate-400 text-xs font-medium" for="service_plan">Service</label>
                <select id="service_plan" name="service_plan" required class="form-input" x-model="plan">
                    <option value="">Choose</option>
                    <?php foreach ($planGroups as $need => $groupLabel): ?>
                        <?php
                        $groupPlans = array();
                        foreach ($servicePlans as $servicePlan) {
                            if ((string) $servicePlan['needs_detail'] === $need) {
                                $groupPlans[] = $servicePlan;
                            }
                        }
                        if (!$groupPlans) {
                            continue;
                        }
                        ?>
                        <optgroup label="<?= e($groupLabel) ?>">
                            <?php foreach ($groupPlans as $servicePlan): ?>
                                <option value="<?= e($servicePlan['code']) ?>"><?= e($servicePlan['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                    <?php
                    $otherPlans = array();
                    foreach ($servicePlans as $servicePlan) {
                        if (!isset($planGroups[(string) $servicePlan['needs_detail']])) {
                            $otherPlans[] = $servicePlan;
                        }
                    }
                    ?>
                    <?php if ($otherPlans): ?>
                        <optgroup label="Other">
                            <?php foreach ($otherPlans as $servicePlan): ?>
                                <option value="<?= e($servicePlan['code']) ?>"><?= e($servicePlan['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <div x-show="needs[plan]==='voice'" x-cloak>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="service_quantity">How many voice calls</label>
                    <input id="service_quantity" name="service_quantity" type="number" min="1" max="500000" class="form-input" placeholder="200" :required="needs[plan]==='voice'">
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="service_detail">Domain or note</label>
                    <input id="service_detail" name="service_detail" maxlength="180" class="form-input" placeholder="shop.com.np, or where the training is">
                </div>
                <button type="submit" name="add_service" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add service</button>
            </form>
        </section>
    </div>

    <section class="dash-panel overflow-hidden">
        <div class="dash-panel-header"><h2 class="font-heading font-semibold text-white">Services</h2></div>
        <?php if ($services): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-800 text-left">
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Service</th>
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Status</th>
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Renews</th>
                            <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($services as $service): ?>
                            <?php
                            $serviceBrief = json_decode((string) $service['order_brief'], true);
                            $officeService = is_array($serviceBrief) && isset($serviceBrief['Added by']) && $serviceBrief['Added by'] === 'the team';
                            $serviceOpen = $service['status'] === 'active' || $service['status'] === 'booked';
                            ?>
                            <tr>
                                <td class="px-4 py-3 text-sm text-white"><?= e($service['service_name']) ?><?= trim((string) $service['detail_label']) !== '' ? ' · ' . e($service['detail_label']) : '' ?></td>
                                <td class="px-4 py-3 text-sm text-slate-300"><?= e(ucfirst((string) $service['status'])) ?></td>
                                <td class="px-4 py-3 text-sm text-slate-400"><?= trim((string) $service['next_renewal']) !== '' ? e($service['next_renewal']) : '—' ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($officeService && $serviceOpen): ?>
                                        <form method="POST" onsubmit="return confirm('Take this service back? It will not renew. Voice calls already placed stay used.');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                            <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                                            <button type="submit" name="take_service" class="text-yellow-300 text-sm bg-transparent border-0 cursor-pointer p-0">Take back</button>
                                        </form>
                                    <?php elseif (!$officeService): ?>
                                        <span class="text-slate-500">Paid from wallet</span>
                                    <?php else: ?>
                                        <span class="text-slate-500">Closed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="p-5 text-slate-500 text-sm">No services on this account yet.</p>
        <?php endif; ?>
    </section>
    </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
