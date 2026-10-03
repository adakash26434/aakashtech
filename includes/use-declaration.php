<?php
$guardKey = isset($guardKey) ? (string) $guardKey : 'message';
$guardToken = billing_form_guard_token($guardKey);
$declarationOn = !empty($declarationAccepted);
$guardMath = isset($guardMath) ? (bool) $guardMath : true;
?>
<div class="honeypot" aria-hidden="true">
    <label for="fax-<?= e($guardKey) ?>">Fax</label>
    <input id="fax-<?= e($guardKey) ?>" type="text" name="fax_number" tabindex="-1" autocomplete="off" value="">
</div>
<input type="hidden" name="form_guard" value="<?= e($guardToken) ?>">
<?php if ($guardMath): ?>
<label class="block text-slate-300 text-sm font-medium mb-2" for="human-<?= e($guardKey) ?>">What is <?= e(auth_math_prompt($guardKey)) ?>?</label>
<input id="human-<?= e($guardKey) ?>" name="human_check" type="text" inputmode="numeric" maxlength="2" required autocomplete="off" class="form-input mb-4" placeholder="Answer">
<?php endif; ?>
<label class="flex items-start gap-3 rounded-xl border border-slate-700 bg-slate-900/60 p-4 cursor-pointer">
    <input type="checkbox" name="legal_accept" value="1" required <?= $declarationOn ? 'checked' : '' ?> class="mt-1">
    <span>
        <span class="block text-white text-sm font-medium mb-1">Declaration</span>
        <span class="block text-slate-300 text-sm leading-relaxed"><?= e(billing_use_declaration()) ?></span>
    </span>
</label>
