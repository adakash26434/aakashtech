<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/../includes/domain-check.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attach_domain'])) {
    verify_csrf();
    $error = domain_admin_attach(
        $conn,
        isset($_POST['domain_client']) ? (int) $_POST['domain_client'] : 0,
        isset($_POST['domain_name']) ? $_POST['domain_name'] : ''
    );
    if ($error === '') {
        $notice = 'Name added as active. The wallet was not charged. The year renews from the wallet.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    verify_csrf();
    $requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $decision = isset($_POST['decision']) ? (string) $_POST['decision'] : '';
    $error = domain_mark_request($conn, $requestId, $decision, isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    if ($error === '') {
        $notice = $decision === 'active' ? 'Marked active. A paid year now shows in the client account and renews from the wallet.' : 'The request was declined. If it was already paid, that amount is back in the wallet.';
    }
}
$domainClients = $conn->query('SELECT id, name, email FROM client_users ORDER BY name ASC LIMIT 200');
$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$rows = array();
try {
    $rows = domain_request_queue($conn, $find);
} catch (Throwable $exception) {
    error_log('Domain requests could not be listed.');
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Domain requests</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Waiting requests stay in view. Older finished names are in the latest 80.' : 'Matches for “' . e($find) . '”.' ?> Register a paid name yourself at the registry, then mark it active. Declining a paid request returns the amount to the wallet. <a class="text-brand-400" href="manual.php#domain">नेपाली चरण</a></p>
</div>
<div x-data="{ tab: '<?= $error !== '' ? 'work' : 'list' ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" role="tab" @click="tab='list'" :class="tab==='list' ? 'is-on' : ''">Request list</button>
    <button type="button" role="tab" @click="tab='work'" :class="tab==='work' ? 'is-on' : ''">Add a name</button>
</div>
<div x-show="tab==='list'">
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Domain, client, or email">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
</div>
<div x-show="tab==='work'" x-cloak>
<section class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Add a name you already registered</h3></div>
    <form method="POST" class="p-5 grid md:grid-cols-3 gap-3 items-end">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="domain_client">Client</label>
            <select id="domain_client" name="domain_client" required class="form-input">
                <option value="">Choose</option>
                <?php if ($domainClients): ?>
                    <?php while ($domainClient = $domainClients->fetch_assoc()): ?>
                        <option value="<?= (int) $domainClient['id'] ?>"><?= e($domainClient['name']) ?> · <?= e($domainClient['email']) ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="domain_name">Name</label>
            <input id="domain_name" name="domain_name" required class="form-input" placeholder="shop.com.np">
        </div>
        <button type="submit" name="attach_domain" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Add as active</button>
    </form>
    <p class="px-5 pb-4 text-slate-500 text-xs">Use this when the name was registered at the office. The wallet is not charged now. The year still renews from the wallet.</p>
</section>
</div>
<?php if ($notice): ?><div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div><?php endif; ?>
<div x-show="tab==='list'">
<?php if (!$rows): ?><div class="dash-panel"><div class="p-6 text-slate-400 text-sm"><?= $find === '' ? 'No domain request yet.' : 'No domain matches that search.' ?></div></div><?php endif; ?>
<?php foreach ($rows as $row): ?>
    <?php
    $price = isset($row['price']) ? (float) $row['price'] : 0;
    $ready = $row['status'] === 'paid' || ($row['status'] === 'requested' && $price <= 0);
    $fileNote = $row['document_path'] !== '' ? domain_registry_file_note((int) $row['client_id'], $row['document_path']) : '';
    ?>
    <article class="dash-panel mb-4">
        <div class="p-6">
            <div class="flex flex-wrap justify-between gap-3 mb-3">
                <h2 class="font-heading font-semibold text-white text-lg"><?= e($row['domain_name']) ?></h2>
                <span class="text-sm text-slate-300"><?php
                    if ($row['status'] === 'requested' && $price > 0) {
                        echo 'Waiting for payment';
                    } elseif ($row['status'] === 'paid') {
                        echo 'Paid';
                    } else {
                        echo e(ucfirst((string) $row['status']));
                    }
                ?></span>
            </div>
            <p class="text-slate-300 text-sm mb-2"><a class="text-white hover:text-brand-300" href="client.php?id=<?= (int) $row['client_id'] ?>"><?= e($row['account_name']) ?></a> · <?php $domainEmail = filter_var($row['email'], FILTER_VALIDATE_EMAIL) ? (string) $row['email'] : ''; ?><?php if ($domainEmail !== ''): ?><a class="text-brand-400 hover:text-brand-300" href="mailto:<?= e($domainEmail) ?>"><?= e($domainEmail) ?></a><?php else: ?><?= e($row['email']) ?><?php endif; ?><?php if (!empty($row['phone'])): ?> · <?php $domainPhone = preg_replace('/[^0-9+]/', '', (string) $row['phone']); ?><?php if ($domainPhone !== ''): ?><a class="text-brand-400 hover:text-brand-300" href="tel:<?= e($domainPhone) ?>"><?= e($row['phone']) ?></a><?php else: ?><?= e($row['phone']) ?><?php endif; ?><?php endif; ?></p>
            <p class="text-slate-400 text-sm mb-2"><?= (isset($row['holder_kind']) && $row['holder_kind'] === 'organization') ? 'Organization' : 'Individual' ?> · <?= e($row['holder_name']) ?></p>
            <?php if (!empty($row['holder_address'])): ?>
                <p class="text-slate-400 text-sm mb-2"><?= e($row['holder_address']) ?></p>
            <?php endif; ?>
            <?php if ($price > 0): ?>
                <p class="text-slate-300 text-sm mb-3">Yearly bill <?= e(billing_money_label($price)) ?>, already including 13% VAT.</p>
            <?php endif; ?>
            <?php if (domain_is_np($row['tld'])): ?>
                <p class="text-slate-400 text-sm mb-3">Register this .<?= e($row['tld']) ?> name at <a class="text-brand-400" href="https://register.com.np/" target="_blank" rel="noopener">register.com.np</a> with the holder, address, and document below.</p>
            <?php else: ?>
                <p class="text-slate-400 text-sm mb-3">Register this .com name at the registrar you use, with the holder and address below.</p>
            <?php endif; ?>
            <?php if ($row['document_path'] !== ''): ?>
                <p class="mb-2"><a class="text-brand-400 text-sm" href="domain-file.php?id=<?= (int) $row['id'] ?>">Open the registry document</a></p>
                <?php if ($fileNote !== ''): ?><p class="text-yellow-200 text-sm mb-3"><?= e($fileNote) ?></p><?php endif; ?>
            <?php endif; ?>
            <?php if ($row['status'] === 'requested' && $price > 0): ?>
                <p class="text-slate-400 text-sm mb-3">Waiting for the client to pay the yearly bill. Decline it if this name should be released.</p>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note-<?= (int) $row['id'] ?>">Why it was not registered</label>
                        <textarea id="note-<?= (int) $row['id'] ?>" name="admin_note" rows="2" maxlength="400" class="form-input resize-none"></textarea>
                    </div>
                    <button type="submit" name="decision" value="declined" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300" onclick="return confirm('Decline this domain request?')">Decline</button>
                </form>
            <?php elseif ($ready): ?>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="decision" value="active" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Mark active</button>
                    <div>
                        <label class="block text-slate-400 text-xs font-medium mb-1.5" for="note-<?= (int) $row['id'] ?>">Why it was not registered</label>
                        <textarea id="note-<?= (int) $row['id'] ?>" name="admin_note" rows="2" maxlength="400" class="form-input resize-none"></textarea>
                    </div>
                    <button type="submit" name="decision" value="declined" class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300" onclick="return confirm('Decline this domain request?')">Decline</button>
                </form>
            <?php elseif ($row['admin_note'] !== ''): ?>
                <p class="text-slate-400 text-sm"><?= e($row['admin_note']) ?></p>
            <?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
