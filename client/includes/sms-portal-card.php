<?php
$kycReady = isset($conn) && billing_kyc_approved($conn, (int) get_client_id());
$balances = isset($conn) ? billing_unit_balances($conn, (int) get_client_id()) : array('sms' => 0);
?>
<section class="dash-panel mb-6">
    <div class="p-6">
        <h2 class="font-heading font-semibold text-white text-lg mb-1">SMS dashboard</h2>
        <?php if (!$kycReady): ?>
            <p class="text-slate-400 text-sm mb-4">You have <?= number_format((int) $balances['sms']) ?> SMS credits. Sending and API tokens open after identity is approved.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        <?php else: ?>
            <p class="text-slate-400 text-sm mb-4"><?= number_format((int) $balances['sms']) ?> SMS credits are on this account. Send from here, or create a token and call it from your own website.</p>
            <a href="sms-portal.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Send SMS</a>
        <?php endif; ?>
    </div>
</section>
