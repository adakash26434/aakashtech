<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$campaigns = $conn->query("SELECT sc.*, cu.name as client_name FROM sms_campaigns sc JOIN client_users cu ON sc.client_id = cu.id ORDER BY sc.created_at DESC");
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Messages</h1>
    <p class="text-slate-500 text-sm">SMS and voice jobs, including the text and number list the client reserved.</p>
</div>

<div class="dash-panel overflow-hidden">
    <?php if ($campaigns && $campaigns->num_rows > 0): ?>
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
                    <?php while ($c = $campaigns->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <p class="text-white text-sm font-medium"><?= e($c['campaign_name']) ?> <span class="text-slate-500"><?= e(isset($c['channel']) && $c['channel'] === 'voice' ? 'Voice' : 'SMS') ?></span></p>
                                <p class="text-slate-500 text-xs max-w-xs max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['message_content']) ?></p>
                                <?php if (!empty($c['recipients_list'])): ?>
                                    <p class="text-slate-600 text-xs max-w-xs max-h-16 overflow-auto whitespace-pre-wrap"><?= e($c['recipients_list']) ?></p>
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
                                ?>"><?= ucfirst($c['status']) ?></span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell"><span class="text-slate-500 text-sm"><?= date('M d, Y', strtotime($c['created_at'])) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-12 text-center text-slate-500 text-sm">No campaigns found.</p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
