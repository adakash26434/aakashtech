<?php
$guardKey = isset($guardKey) ? (string) $guardKey : 'message';
$guardToken = billing_form_guard_token($guardKey);
$declarationOn = !empty($declarationAccepted);
?>
<div class="honeypot" aria-hidden="true">
    <label for="fax-<?= e($guardKey) ?>">Fax</label>
    <input id="fax-<?= e($guardKey) ?>" type="text" name="fax_number" tabindex="-1" autocomplete="off" value="">
</div>
<input type="hidden" name="form_guard" value="<?= e($guardToken) ?>">
<label class="flex items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/60 p-4">
    <input type="checkbox" name="legal_accept" value="1" required <?= $declarationOn ? 'checked' : '' ?> class="mt-1">
    <span>
        <span class="block text-white text-sm font-medium mb-1">Declaration</span>
        <span class="block text-slate-300 text-sm leading-relaxed"><?= e(billing_use_declaration()) ?></span>
    </span>
</label>
