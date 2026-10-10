<?php
$kycReady = isset($conn) && billing_kyc_approved($conn, (int) get_client_id());
$sendOpen = true;
$balances = isset($conn) ? billing_unit_balances($conn, (int) get_client_id()) : array('sms' => 0, 'voice_calls' => 0);
$smsCredits = (int) (isset($balances['sms']) ? $balances['sms'] : 0);
$voiceCredits = (int) (isset($balances['voice_calls']) ? $balances['voice_calls'] : 0);
$showSms = $smsCredits > 0 || $voiceCredits === 0;
$showVoice = $voiceCredits > 0;
?>
<section class="dash-panel mb-6" aria-labelledby="messages-title">
    <div class="p-6">
        <h2 id="messages-title" class="font-heading font-semibold text-white text-lg mb-4">Messages</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <?php if ($showSms): ?>
                <div class="rounded-xl border border-slate-700/60 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">SMS</p>
                    <p class="text-2xl font-semibold text-white mt-1"><?= number_format($smsCredits) ?> <span class="text-sm font-normal text-slate-400">credits</span></p>
                    <?php if (!$kycReady): ?>
                        <p class="text-slate-400 text-sm mt-2">Up to 100 SMS before identity is approved. A larger send needs identity.</p>
                    <?php else: ?>
                        <p class="text-slate-400 text-sm mt-2">Send from here, or call it from your own website with a token.</p>
                    <?php endif; ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="sms-portal.php" class="inline-block px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Send SMS</a>
                        <?php if (!$kycReady): ?>
                            <a href="kyc.php" class="inline-block px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300">Submit identity</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($showVoice): ?>
                <div class="rounded-xl border border-slate-700/60 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Voice calls</p>
                    <p class="text-2xl font-semibold text-white mt-1"><?= number_format($voiceCredits) ?> <span class="text-sm font-normal text-slate-400">calls</span></p>
                    <p class="text-slate-400 text-sm mt-2">Save the script under Messages. The team places the call.</p>
                    <div class="mt-4">
                        <a href="campaigns.php" class="inline-block px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Open Messages</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
