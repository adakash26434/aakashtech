<?php
$totpNote = isset($totpNote) && is_array($totpNote) ? $totpNote : array('msg' => '', 'err' => '');
$totpView = isset($totpView) && is_array($totpView) ? $totpView : array('mode' => 'idle', 'uri' => '', 'grouped' => '', 'codes' => array());
$totpMode = isset($totpView['mode']) ? (string) $totpView['mode'] : 'idle';
?>
<div class="dash-panel lg:col-span-2">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Google Authenticator</h3></div>
    <div class="p-5 space-y-4">
        <?php if (!empty($totpNote['msg'])): ?>
            <div class="p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($totpNote['msg']) ?></div>
        <?php endif; ?>
        <?php if (!empty($totpNote['err'])): ?>
            <div class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($totpNote['err']) ?></div>
        <?php endif; ?>
        <p class="text-slate-400 text-sm">Every sign-in asks for the 6-digit code after the password. This cannot be turned off. A backup code works once when the phone is not available.</p>
        <?php if ($totpMode === 'codes'): ?>
            <ul class="grid sm:grid-cols-2 gap-2 max-w-md">
                <?php foreach ($totpView['codes'] as $backupCode): ?>
                    <li class="bg-dark-950 border border-slate-800 rounded-lg px-3 py-2 text-white font-mono text-sm tracking-wide"><?= e($backupCode) ?></li>
                <?php endforeach; ?>
            </ul>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="totp_action" value="dismiss_codes">
                <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">I have saved these codes</button>
            </form>
        <?php elseif ($totpMode === 'qr'): ?>
            <div><?= totp_qr_html($totpView['uri'], 'totp-rekey-qr') ?></div>
            <p class="text-white font-mono text-sm tracking-wide"><?= e($totpView['grouped']) ?></p>
            <p class="text-slate-500 text-xs">Add it in Google Authenticator as a time-based 6-digit key. The old entry stops working after this code is confirmed.</p>
            <form method="POST" class="space-y-3 max-w-sm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="totp_action" value="confirm_rekey">
                <input name="authenticator_code" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="form-input tracking-widest" placeholder="6-digit code from the new entry" aria-label="6-digit code from the new entry">
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Confirm new phone</button>
                </div>
            </form>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="totp_action" value="cancel_rekey">
                <button type="submit" class="text-slate-400 text-sm">Cancel</button>
            </form>
        <?php else: ?>
            <div class="grid md:grid-cols-2 gap-4">
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="totp_action" value="backup">
                    <p class="text-white text-sm font-medium">New backup codes</p>
                    <input type="password" name="totp_password" required autocomplete="current-password" class="form-input" placeholder="Current password" aria-label="Current password for new backup codes">
                    <input name="authenticator_code" required autocomplete="one-time-code" maxlength="12" autocapitalize="characters" spellcheck="false" class="form-input" placeholder="Current 6-digit code or a backup code" aria-label="Current authenticator code for new backup codes">
                    <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Create new backup codes</button>
                </form>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="totp_action" value="rekey">
                    <p class="text-white text-sm font-medium">Set up a new phone</p>
                    <input type="password" name="totp_password" required autocomplete="current-password" class="form-input" placeholder="Current password" aria-label="Current password for a new phone">
                    <input name="authenticator_code" required autocomplete="one-time-code" maxlength="12" autocapitalize="characters" spellcheck="false" class="form-input" placeholder="Current 6-digit code or a backup code" aria-label="Current authenticator code for a new phone">
                    <button type="submit" class="px-6 py-2.5 border border-slate-600 text-white text-sm font-medium rounded-xl transition">Set up a new phone</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
