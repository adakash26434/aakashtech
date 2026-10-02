<?php
$portalUrl = billing_sms_portal_url();
$portalLogin = isset($portalLogin) && is_array($portalLogin) ? $portalLogin : array('username' => '', 'password' => '');
$portalReady = $portalLogin['username'] !== '' && $portalLogin['password'] !== '';
$kycReady = isset($conn) && billing_kyc_approved($conn, (int) get_client_id());
?>
<section class="dash-panel mb-6">
    <div class="p-6">
        <h2 class="font-heading font-semibold text-white text-lg mb-1">SMS portal</h2>
        <?php if (!$kycReady): ?>
            <p class="text-slate-400 text-sm mb-4">The portal login appears after your identity is approved. Until then, SMS and voice credits can be bought, but sending stays closed.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        <?php else: ?>
        <p class="text-slate-400 text-sm mb-4">Buy the credit here. Send the SMS from the portal with the username and password for your account on that system. This is not the password for this client panel.</p>
        <a href="<?= e($portalUrl) ?>" target="_blank" rel="noopener" class="text-brand-400 text-sm break-all"><?= e($portalUrl) ?></a>
        <?php if ($portalReady): ?>
            <dl class="mt-4 space-y-3 text-sm" x-data="{ showPassword: false }">
                <div>
                    <dt class="text-slate-500 text-xs mb-1">Username</dt>
                    <dd class="text-white font-medium"><?= e($portalLogin['username']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500 text-xs mb-1">Password</dt>
                    <dd class="flex items-center gap-3">
                        <span class="text-white font-medium" x-show="!showPassword">••••••••</span>
                        <span class="text-white font-medium" x-show="showPassword" style="display:none;"><?= e($portalLogin['password']) ?></span>
                        <button type="button" class="text-brand-400 text-xs" @click="showPassword = !showPassword" x-text="showPassword ? 'Hide' : 'Show'"></button>
                    </dd>
                </div>
            </dl>
        <?php else: ?>
            <p class="text-yellow-300 text-sm mt-4">Your SMS or voice service is active. The portal username and password will appear here when the account is ready.</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
