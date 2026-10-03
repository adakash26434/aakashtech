<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_voice'])) {
    verify_csrf();
    $error = voice_place_job($conn, isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : 0);
    if ($error === '') {
        $notice = 'Voice job marked placed. The client’s voice credits were used.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_voice'])) {
    verify_csrf();
    $error = voice_return_job($conn, isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : 0);
    if ($error === '') {
        $notice = 'Voice credits returned. The job is waiting again.';
    }
}

$find = admin_find_text(isset($_GET['q']) ? $_GET['q'] : '');
$campaignSql = 'SELECT sc.*, cu.name as client_name FROM sms_campaigns sc JOIN client_users cu ON sc.client_id = cu.id ';
$campaignRows = array();
$campaignSeen = array();
if ($find !== '') {
    $like = '%' . $find . '%';
    $campaignStmt = $conn->prepare($campaignSql . 'WHERE sc.campaign_name LIKE ? OR cu.name LIKE ? OR cu.email LIKE ? ORDER BY sc.created_at DESC LIMIT 50');
    $campaignStmt->bind_param('sss', $like, $like, $like);
    $campaignStmt->execute();
    $campaignRows = db_fetch_all($campaignStmt);
    $campaignStmt->close();
} else {
    foreach (array(
        $campaignSql . "WHERE sc.channel = 'voice' AND sc.status IN ('draft','scheduled') ORDER BY sc.created_at DESC LIMIT 50",
        $campaignSql . 'ORDER BY sc.created_at DESC LIMIT 100'
    ) as $campaignQuery) {
        $campaignResult = $conn->query($campaignQuery);
        if (!$campaignResult) {
            continue;
        }
        while ($campaignRow = $campaignResult->fetch_assoc()) {
            $campaignId = (int) $campaignRow['id'];
            if (isset($campaignSeen[$campaignId])) {
                continue;
            }
            $campaignSeen[$campaignId] = true;
            $campaignRows[] = $campaignRow;
        }
    }
}
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Messages</h1>
    <p class="text-slate-500 text-sm"><?= $find === '' ? 'Latest messages, with waiting voice jobs kept in view.' : 'Matches for “' . e($find) . '”.' ?> Place a voice job on your own voice line, then mark it placed. That uses the client’s voice credits.</p>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="<?= e($find) ?>" class="form-input max-w-sm" placeholder="Client or message name">
    <button type="submit" class="px-4 py-2 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl">Find</button>
</form>
<?php if ($notice): ?><div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($error) ?></div><?php endif; ?>

<div class="dash-panel overflow-hidden">
    <?php if ($campaignRows): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 text-left">
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Campaign</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden md:table-cell">Client</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Recipients</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-slate-400 text-xs font-medium uppercase tracking-wider hidden lg:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($campaignRows as $c): ?>
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <p class="text-white text-sm font-medium"><?= e($c['campaign_name']) ?> <span class="text-slate-500"><?= e(isset($c['channel']) && $c['channel'] === 'voice' ? 'Voice' : 'SMS') ?></span></p>
                                <p class="text-slate-500 text-xs max-w-xs max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['message_content']) ?></p>
                                <?php if (!empty($c['recipients_list'])): ?>
                                    <p class="text-slate-600 text-xs max-w-xs max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['recipients_list']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($c['language']) || !empty($c['scheduled_at'])): ?>
                                    <p class="text-slate-400 text-xs mt-1"><?php if (!empty($c['language'])): ?><?= e($c['language']) ?><?php endif; ?><?php if (!empty($c['scheduled_at'])): ?><?= !empty($c['language']) ? ' · ' : '' ?><?= e($c['scheduled_at']) ?><?php endif; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($c['declaration_text'])): ?>
                                    <p class="text-slate-400 text-xs mt-1 max-w-xs max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['declaration_text']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell"><span class="text-slate-300 text-sm"><?= e($c['client_name']) ?></span></td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-300 text-sm"><?= number_format($c['recipients_count']) ?></span></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-[10px] font-medium rounded-full <?=
                                    $c['status'] === 'sent' ? 'bg-green-500/20 text-green-400' :
                                    ($c['status'] === 'sending' ? 'bg-blue-500/20 text-blue-400' :
                                    ($c['status'] === 'scheduled' ? 'bg-yellow-500/20 text-yellow-400' :
                                    ($c['status'] === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-slate-600/20 text-slate-400')))
                                ?>"><?php
                                    if (isset($c['channel']) && $c['channel'] === 'voice' && $c['status'] === 'sent') {
                                        echo 'Placed';
                                    } elseif (isset($c['channel']) && $c['channel'] === 'voice' && $c['status'] === 'draft') {
                                        echo 'Waiting';
                                    } else {
                                        echo e(ucfirst((string) $c['status']));
                                    }
                                ?></span>
                                <?php if (isset($c['channel']) && $c['channel'] === 'voice' && ($c['status'] === 'draft' || $c['status'] === 'scheduled')): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                                        <button type="submit" name="place_voice" value="1" class="text-brand-400 text-xs">Mark placed</button>
                                    </form>
                                <?php elseif (isset($c['channel']) && $c['channel'] === 'voice' && $c['status'] === 'sent'): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                                        <button type="submit" name="return_voice" value="1" class="text-slate-500 text-xs">Return credits</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-500 text-sm"><?= date('M d, Y', strtotime($c['created_at'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm">No campaigns found.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
