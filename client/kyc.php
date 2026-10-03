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
        $msg = 'Identity submitted. Please wait for approval. More than 100 SMS stays closed until KYC is approved.';
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
    <p class="text-slate-500 text-sm">More than 100 SMS needs KYC. Choose Personal or Organization. Only that form is shown. <a class="text-brand-400" href="manual.php#kyc">नेपाली चरण</a></p>
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
        <p class="text-slate-400 text-sm mb-5">Please update KYC before sending more than 100 SMS. A personal account needs the citizenship certificate, front and back, and the National Identity Card number. An organization needs company details, PAN, registration, and the latest tax clearance. After approval, these details cannot be edited here.</p>
        <?php if ($locked): ?>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-slate-500">Account</dt><dd class="text-white"><?= $kind === 'organization' ? 'Organization' : 'Individual' ?></dd></div>
                <?php if ($kind === 'individual'): ?>
                    <div><dt class="text-slate-500">Name</dt><dd class="text-white"><?= e($kyc['full_name']) ?></dd></div>
                    <div><dt class="text-slate-500">National Identity Card number</dt><dd class="text-white"><?= e($kyc['id_number']) ?></dd></div>
                <?php else: ?>
                    <div><dt class="text-slate-500">Company</dt><dd class="text-white"><?= e($kyc['org_name']) ?></dd></div>
                    <div><dt class="text-slate-500">Registration number</dt><dd class="text-white"><?= e($kyc['registration_number']) ?></dd></div>
                    <div><dt class="text-slate-500">PAN</dt><dd class="text-white"><?= e($kyc['tax_number']) ?></dd></div>
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
                <div class="kyc-switch">
                    <label class="kyc-choice" for="kyc-individual"><span>Personal</span><small>Citizenship and National ID</small></label>
                    <label class="kyc-choice" for="kyc-organization"><span>Organization</span><small>Company, PAN, and tax clearance</small></label>
                </div>
                <div class="kyc-fields kyc-fields-individual space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="full_name">Full name</label>
                        <input id="full_name" type="text" name="full_name" maxlength="160" class="form-input" value="<?= e($kyc['full_name']) ?>">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="id_number">National Identity Card number</label>
                        <input id="id_number" type="text" name="id_number" maxlength="40" class="form-input" value="<?= e($kyc['id_number']) ?>" placeholder="Number on the National Identity Card">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_identity">Citizenship, front</label>
                            <?php if ($kyc['doc_identity'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=identity">Current front</a></p><?php endif; ?>
                            <input id="doc_identity" type="file" name="doc_identity" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_identity_back">Citizenship, back</label>
                            <?php if ($kyc['doc_identity_back'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=identity_back">Current back</a></p><?php endif; ?>
                            <input id="doc_identity_back" type="file" name="doc_identity_back" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                        </div>
                    </div>
                </div>
                <div class="kyc-fields kyc-fields-organization space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="org_name">Company name</label>
                        <input id="org_name" type="text" name="org_name" maxlength="200" class="form-input" value="<?= e($kyc['org_name']) ?>">
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="registration_number">Registration number</label>
                            <input id="registration_number" type="text" name="registration_number" maxlength="40" class="form-input" value="<?= e($kyc['registration_number']) ?>">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="tax_number">PAN number</label>
                            <input id="tax_number" type="text" name="tax_number" maxlength="40" class="form-input" value="<?= e($kyc['tax_number']) ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_registration">Registration certificate</label>
                        <?php if ($kyc['doc_registration'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=registration">Current certificate</a></p><?php endif; ?>
                        <input id="doc_registration" type="file" name="doc_registration" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_tax">PAN certificate</label>
                        <?php if ($kyc['doc_tax'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=tax">Current certificate</a></p><?php endif; ?>
                        <input id="doc_tax" type="file" name="doc_tax" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
                    </div>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="doc_clearance">Latest tax clearance</label>
                        <?php if ($kyc['doc_clearance'] !== ''): ?><p class="text-slate-500 text-xs mb-1.5"><a class="text-brand-400" href="kyc-file.php?slot=clearance">Current tax clearance</a></p><?php endif; ?>
                        <input id="doc_clearance" type="file" name="doc_clearance" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" class="form-input">
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
.kyc-switch { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 520px) { .kyc-switch { grid-template-columns: 1fr; } }
.kyc-choice { display: flex; flex-direction: column; gap: 2px; min-height: 72px; justify-content: center; padding: 12px 14px; border: 1px solid #cddbd6; border-radius: 14px; color: #16343a; background: #fff; cursor: pointer; }
.kyc-choice small { color: #536b63; font-size: 12px; }
#kyc-individual:checked ~ .kyc-switch label[for="kyc-individual"],
#kyc-organization:checked ~ .kyc-switch label[for="kyc-organization"] { border-color: #0b8b7a; background: #e5f5f0; color: #075e54; }
.kyc-fields { display: none; }
#kyc-individual:checked ~ .kyc-fields-individual,
#kyc-organization:checked ~ .kyc-fields-organization { display: block; }
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
