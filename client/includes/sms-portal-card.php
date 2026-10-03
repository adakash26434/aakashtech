<?php
$kycReady = isset($conn) && billing_kyc_approved($conn, (int) get_client_id());
$sendOpen = true;
$balances = isset($conn) ? billing_unit_balances($conn, (int) get_client_id()) : array('sms' => 0, 'voice_calls' => 0);
$smsCredits = (int) (isset($balances['sms']) ? $balances['sms'] : 0);
$voiceCredits = (int) (isset($balances['voice_calls']) ? $balances['voice_calls'] : 0);
$showSms = $smsCredits > 0 || $voiceCredits === 0;
$showVoice = $voiceCredits > 0;
?>
<section class="dash-panel mb-6">
    <div class="p-6">
        <h2 class="font-heading font-semibold text-white text-lg mb-1">Messages</h2>
        <?php if ($showSms && !$kycReady): ?>
            <p class="text-slate-400 text-sm mb-4"><?= number_format($smsCredits) ?> SMS credits are on this account. Up to 100 SMS can be sent before identity is approved. A larger send needs identity.</p>
            <a href="sms-portal.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Send SMS</a>
            <a href="kyc.php" class="inline-block px-6 py-2.5 ml-2 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Submit identity</a>
        <?php elseif ($showSms): ?>
            <p class="text-slate-400 text-sm mb-4"><?= number_format($smsCredits) ?> SMS credits are on this account. Send from here, or create a token and call it from your own website.</p>
            <a href="sms-portal.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Send SMS</a>
        <?php endif; ?>
        <?php if ($showVoice): ?>
            <p class="text-slate-400 text-sm <?= $showSms ? 'mt-4' : '' ?> mb-4"><?= number_format($voiceCredits) ?> voice credits are on this account. Save the script under Messages. The team places the call.</p>
            <a href="campaigns.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Open Messages</a>
        <?php endif; ?>
    </div>
</section>
