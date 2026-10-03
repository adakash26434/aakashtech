<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$cid = (int) get_client_id();
$kyc = billing_kyc_load($conn, $cid);
$msg = '';
$err = '';
$locked = $kyc['status'] === 'approved';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $err = billing_kyc_submit($conn, $cid, $_POST, $_FILES);
    $kyc = billing_kyc_load($conn, $cid);
    $locked = $kyc['status'] === 'approved';
    if ($err === '') {
        $msg = 'Identity submitted. SMS sending opens after it is approved. You can still correct it until then.';
    }
}

$kind = $kyc['account_kind'] === 'organization' ? 'organization' : 'individual';
$statusLabel = array(
    '' => 'Not submitted',
    'pending' => 'Waiting for approval',
    'approved' => 'Approved',
    'rejected' => 'Needs a change'
);
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Identity</h1>
    <p class="text-slate-500 text-sm">Who is sending, and what the messages are for. SMS sending stays closed until this is approved.</p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>
<?php if ($kyc['status'] === 'rejected' && $kyc['admin_note'] !== ''): ?>
    <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm"><?= e($kyc['admin_note']) ?></div>
<?php endif; ?>

<div class="dash-panel max-w-3xl">
    <div class="dash-panel-header">
        <h3 class="font-heading font-semibold text-white"><?= e(isset($statusLabel[$kyc['status']]) ? $statusLabel[$kyc['status']] : 'Not submitted') ?></h3>
    </div>
    <div class="p-5">
        <p class="text-slate-400 text-sm mb-5">An organization submits its registration certificate, PAN or VAT certificate, and the authorized person's citizenship or National Identity Card. An individual submits that person's own citizenship or National Identity Card. This matches what Nepal Telecom asks before web bulk SMS is opened. After approval, these details cannot be edited here.</p>
        <?php if ($locked): ?>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-slate-500">Account</dt><dd class="text-white"><?= $kind === 'organization' ? 'Organization' : 'Individual' ?></dd></div>
                <?php if ($kind === 'individual'): ?>
                    <div><dt class="text-slate-500">Name</dt><dd class="text-white"><?= e($kyc['full_name']) ?></dd></div>
                    <div><dt class="text-slate-500"><?= e(billing_kyc_id_label($kyc['id_kind'])) ?></dt><dd class="text-white"><?= e($kyc['id_number']) ?></dd></div>
                <?php else: ?>
                    <div><dt class="text-slate-500">Organization</dt><dd class="text-white"><?= e($kyc['org_name']) ?></dd></div>
                    <div><dt class="text-slate-500">Registration number</dt><dd class="text-white"><?= e($kyc['registration_number']) ?></dd></div>
                    <div><dt class="text-slate-500">PAN or VAT</dt><dd class="text-white"><?= e($kyc['tax_number']) ?></dd></div>
                    <div><dt class="text-slate-500">Authorized person</dt><dd class="text-white"><?= e($kyc['contact_name']) ?> · <?= e(billing_kyc_id_label($kyc['contact_id_kind'])) ?> <?= e($kyc['contact_id_number']) ?></dd></div>
                <?php endif; ?>
                <div><dt class="text-slate-500">Address</dt><dd class="text-white"><?= e($kyc['address']) ?></dd></div>
                <div><dt class="text-slate-500">Use</dt><dd class="text-white"><?= e($kyc['purpose']) ?></dd></div>
            </dl>
            <p class="text-slate-500 text-sm mt-5">Approved. A change has to be requested from Support.</p>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data" class="kyc-form space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="radio" name="account_kind" id="kyc-individual" value="individual" <?= $kind === 'individual' ? 'checked' : '' ?>>
                <input type="radio" name="account_kind" id="kyc-organization" value="organization" <?= $kind === 'organization' ? 'checked' : '' ?>>
                <div class="flex flex-wrap gap-3">
                    <label class="kyc-choice" for="kyc-individual">Individual</label>
                    <label class="kyc-choice" for="kyc-organization">Organization</label>
                </div>
                <div class="kyc-fields kyc-fields-individual space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="full_name">Name on the document</label>
                        <input id="full_name" type="text" name="full_name" maxlength="160" class="form-input" value="<?= e($kyc['full_name']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="id_kind">Document</label>
                        <select id="id_kind" name="id_kind" class="form-input">
                            <option value="citizenship" <?= $kyc['id_kind'] !== 'national_id' ? 'selected' : '' ?>>Citizenship certificate</option>
                            <option value="national_id" <?= $kyc['id_kind'] === 'national_id' ? 'selected' : '' ?>>National Identity Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="id_number">Document number</label>
                        <input id="id_number" type="text" name="id_number" maxlength="40" class="form-input" value="<?= e($kyc['id_number']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_identity">Upload the document</label>
                        <?php if ($kyc['doc_identity'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=identity">Current document</a>. Upload again only to replace it.</p><?php endif; ?>
                        <input id="doc_identity" type="file" name="doc_identity" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                </div>
                <div class="kyc-fields kyc-fields-organization space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="org_name">Organization name</label>
                        <input id="org_name" type="text" name="org_name" maxlength="200" class="form-input" value="<?= e($kyc['org_name']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="registration_number">Registration number</label>
                        <input id="registration_number" type="text" name="registration_number" maxlength="40" class="form-input" value="<?= e($kyc['registration_number']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="tax_number">PAN or VAT number</label>
                        <input id="tax_number" type="text" name="tax_number" maxlength="40" class="form-input" value="<?= e($kyc['tax_number']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_registration">Registration certificate</label>
                        <?php if ($kyc['doc_registration'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=registration">Current certificate</a>.</p><?php endif; ?>
                        <input id="doc_registration" type="file" name="doc_registration" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_tax">PAN or VAT certificate</label>
                        <?php if ($kyc['doc_tax'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=tax">Current certificate</a>.</p><?php endif; ?>
                        <input id="doc_tax" type="file" name="doc_tax" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="contact_name">Authorized person</label>
                        <input id="contact_name" type="text" name="contact_name" maxlength="160" class="form-input" value="<?= e($kyc['contact_name']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="contact_id_kind">That person's document</label>
                        <select id="contact_id_kind" name="contact_id_kind" class="form-input">
                            <option value="citizenship" <?= $kyc['contact_id_kind'] !== 'national_id' ? 'selected' : '' ?>>Citizenship certificate</option>
                            <option value="national_id" <?= $kyc['contact_id_kind'] === 'national_id' ? 'selected' : '' ?>>National Identity Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="contact_id_number">Document number</label>
                        <input id="contact_id_number" type="text" name="contact_id_number" maxlength="40" class="form-input" value="<?= e($kyc['contact_id_number']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_authority">Authorized person's identity document</label>
                        <?php if ($kyc['doc_authority'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=authority">Current document</a>.</p><?php endif; ?>
                        <input id="doc_authority" type="file" name="doc_authority" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="kyc_address">Address</label>
                    <textarea id="kyc_address" name="address" rows="2" maxlength="300" class="form-input resize-none"><?= e($kyc['address']) ?></textarea>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5" for="purpose">What the SMS and voice calls are for</label>
                    <textarea id="purpose" name="purpose" rows="3" maxlength="500" class="form-input resize-none" placeholder="Member notices, school notices, customers, or another lawful use"><?= e($kyc['purpose']) ?></textarea>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<style>
.kyc-form > input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.kyc-choice { display: inline-flex; align-items: center; min-height: 42px; padding: 0 14px; border: 1px solid #cddbd6; border-radius: 12px; color: #16343a; background: #fff; cursor: pointer; }
#kyc-individual:checked ~ .flex label[for="kyc-individual"],
#kyc-organization:checked ~ .flex label[for="kyc-organization"] { border-color: #0b8b7a; color: #075e54; }
.kyc-fields { display: none; }
#kyc-individual:checked ~ .kyc-fields-individual,
#kyc-organization:checked ~ .kyc-fields-organization { display: block; }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
