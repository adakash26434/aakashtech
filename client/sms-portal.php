<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/sms-portal-actions.php';
?>
<div class="mb-6 flex items-end justify-between flex-wrap gap-4">
    <div>
        <h1 class="font-heading font-bold text-white text-2xl mb-2">Send SMS</h1>
        <div class="sms-head-strip">
            <span class="sms-head-pill is-main"><strong><?= number_format((int) $balances['sms']) ?></strong> credits left</span>
            <span class="sms-head-pill"><strong><?= number_format($sentToday) ?></strong> sent today</span>
            <a class="sms-head-link" href="sms-logs.php">SMS logs</a>
            <a class="sms-head-link" href="sms-api.php">API token</a>
            <a class="sms-head-link" href="manual.php#sms">नेपाली चरण</a>
        </div>
    </div>
    <a href="shop.php?service=bulk-sms" class="px-4 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl transition">Buy SMS credit</a>
</div>

<?php if ($notice): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($notice) ?></div>
<?php endif; ?>
<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?><?php if ($sentLog > 0): ?> <a class="text-brand-300" href="sms-logs.php?send=<?= (int) $sentLog ?>">Open this send</a><?php endif; ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php if (!$sendOpen): ?>
    <div class="dash-panel mb-6">
        <div class="p-6">
            <h2 class="font-heading font-semibold text-white text-lg mb-1">Identity first</h2>
            <p class="text-slate-400 text-sm mb-4">This account can send <?= number_format((int) $unverifiedRoom) ?> more SMS before identity is approved. A send over 100 SMS in total needs identity, so a new account cannot be used for a large blast.</p>
            <a href="kyc.php" class="inline-block px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Submit identity</a>
        </div>
    </div>
<?php else: ?>
    <?php if (!$kycReady): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">More than 100 SMS needs KYC. <?= number_format((int) $unverifiedRoom) ?> SMS are still open on this account. Please update KYC. <a href="kyc.php" class="text-brand-300">Update KYC</a></div>
    <?php endif; ?>
    <?php if (!$route['connected']): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">SMS credits stay on this account. Sending opens when the line is connected.</div>
    <?php elseif ((int) $balances['sms'] < 1): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm">This account has no SMS credits. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy credit</a> before sending.</div>
    <?php elseif ((int) $balances['sms'] < 100): ?>
        <div class="mb-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-200 text-sm"><?= number_format((int) $balances['sms']) ?> SMS credits left. <a href="shop.php?service=bulk-sms" class="text-brand-300">Buy more</a> before a larger send is refused.</div>
    <?php endif; ?>
    <div class="grid lg:grid-cols-5 gap-6 mb-6">
        <div class="dash-panel lg:col-span-5 min-w-0">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">New SMS</h3></div>
            <form id="sms-send" method="POST" autocomplete="off" class="p-5 space-y-5" x-data='smsComposer(<?= json_encode($composer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' @submit="if (sending || !reviewing) { $event.preventDefault() } else { sending = true }">
                <input type="hidden" name="send_sms" value="1" :disabled="!reviewing">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php
                $audiencePick = $values['audience'] !== '' ? $values['audience'] : 'cooperative';
                $purposePick = $values['purpose'] !== '' ? $values['purpose'] : 'notice';
                if (!isset($audiences[$audiencePick])) {
                    $audiencePick = (string) key($audiences);
                }
                if (!isset($purposes[$purposePick])) {
                    $purposePick = (string) key($purposes);
                }
                ?>
                <?php if ($route['choose_sender']): ?>
                <div>
                    <label class="block text-slate-400 text-xs font-medium mb-1.5">Sender name</label>
                    <select name="sender_id" required class="form-input">
                        <option value="">Select an approved name</option>
                        <?php foreach ($route['approved'] as $senderName): ?>
                            <option value="<?= e($senderName) ?>" <?= strtoupper($values['sender_id']) === $senderName ? 'selected' : '' ?>><?= e($senderName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="sender_id" value="<?= e($phoneName) ?>">
                <?php endif; ?>
                <div class="grid lg:grid-cols-2 gap-6">
                <div class="sms-step">
                    <div class="sms-step-head">
                        <span class="sms-step-no">1</span>
                        <label class="sms-step-title" for="sms-numbers">Who gets it</label>
                        <span class="sms-count-chip" :class="estimate().count ? 'is-on' : ''" x-text="estimate().count ? (estimate().count + ' numbers') : 'Up to 50,000'"></span>
                    </div>
                    <div class="sms-tools">
                        <button type="button" class="sms-upload-open" @click="uploadOpen = true; importOk = false; importNote = ''">&#8679; Upload Excel or CSV</button>
                        <select class="form-input sms-tool-select" @change="pickList($event.target.value); $event.target.selectedIndex = 0">
                            <option value="">Saved lists</option>
                                <?php
                                $listGroups = array('program' => 'Program', 'regular' => 'Regular');
                                foreach ($listGroups as $listKind => $listHeading):
                                    $groupLists = array();
                                    foreach ($numberLists as $listRow) {
                                        $rowKind = (isset($listRow['list_kind']) && $listRow['list_kind'] === 'regular') ? 'regular' : 'program';
                                        if ($rowKind === $listKind) {
                                            $groupLists[] = $listRow;
                                        }
                                    }
                                    if (!$groupLists) {
                                        continue;
                                    }
                                ?>
                                    <optgroup label="<?= e($listHeading) ?>">
                                        <?php foreach ($groupLists as $listRow): ?>
                                            <?php $listCount = $listRow['numbers_text'] === '' ? 0 : substr_count(trim((string) $listRow['numbers_text']), "\n") + 1; ?>
                                            <option value="<?= (int) $listRow['id'] ?>"><?= e($listRow['label']) ?> (<?= (int) $listCount ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        <button type="button" class="sms-clear" x-show="String(numbers || '').trim() !== ''" x-cloak @click="if (confirm('Clear all numbers?')) { numbers = ''; reviewing = false; importNote = ''; }">Clear</button>
                    </div>
                    <input type="text" inputmode="numeric" autocomplete="off" class="form-input mb-2" placeholder="Type one mobile, then press Enter" @keydown.enter.prevent="addNumber($event.target)">
                    <textarea id="sms-numbers" name="numbers" x-model="numbers" required rows="9" autocomplete="off" class="form-input sms-numbers" placeholder="9800000001&#10;Ram, 9800000002" @input="reviewing = false; reviewNote = ''"><?= e($values['numbers']) ?></textarea>
                    <p class="text-xs mt-2" :class="importOk ? 'text-emerald-600' : 'text-slate-500'" x-show="importNote && !uploadOpen" x-text="importNote"></p>
                    <div class="sms-upload-shade" x-show="uploadOpen" x-cloak @click.self="uploadOpen = false" @keydown.escape.window="uploadOpen = false">
                        <div class="sms-upload-box" role="dialog" aria-modal="true" aria-labelledby="sms-upload-title">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p id="sms-upload-title" class="sms-upload-title">Upload numbers</p>
                                    <p class="text-slate-500 text-xs mt-1">Excel .xlsx or .csv, up to 5 MB and 50,000 numbers.</p>
                                </div>
                                <button type="button" class="sms-upload-close" @click="uploadOpen = false" aria-label="Close">&times;</button>
                            </div>
                            <label class="sms-drop" :class="{ 'is-over': dragging, 'is-busy': importing }" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; importDropped($event)">
                                <input type="file" accept=".xlsx,.csv,.txt,text/csv,text/plain" class="hidden" @change="importFile($event)" :disabled="importing">
                                <span class="sms-drop-icon" aria-hidden="true">&#8679;</span>
                                <span class="sms-drop-main" x-text="importing ? 'Reading the file…' : 'Choose a file, or drop it here'"></span>
                                <span class="text-slate-500 text-xs">.xlsx · .csv</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mt-3" x-show="String(numbers || '').trim() !== ''">
                                <input type="checkbox" x-model="keepTyped">
                                Keep the numbers already in the box
                            </label>
                            <p class="text-xs mt-3" :class="importOk ? 'text-emerald-600' : 'text-rose-600'" x-show="importNote && !importing" x-text="importNote"></p>
                            <ul class="sms-upload-tips">
                                <li>Put a heading <b>mobile</b> on the number column. To use {name}, add <b>firstname</b> and <b>lastname</b>, or <b>name</b>.</li>
                                <li>Nepali headings मोबाइल, नाम, थर and Nepali digits ९८०… are read too.</li>
                                <li>98…, 977 98… and 098… all work. Repeated numbers are kept once and wrong ones are skipped.</li>
                            </ul>
                            <div class="sms-upload-samples">
                                <span>Sample files:</span>
                                <a href="sms-sample.php?type=mobile">Mobile only</a>
                                <a href="sms-sample.php?type=names">First name, last name, mobile</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sms-step">
                    <div class="sms-step-head">
                        <span class="sms-step-no">2</span>
                        <label class="sms-step-title" for="sms-text">Message</label>
                        <select class="form-input sms-tool-select ml-auto" @change="pickTemplate($event.target.value); $event.target.selectedIndex = 0">
                            <option value="">Insert a sample</option>
                            <optgroup label="सहकारी नमूना">
                                <?php foreach ($sampleMessages as $sampleMessage): ?>
                                    <option value="<?= e($sampleMessage['id']) ?>"><?= e($sampleMessage['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php if ($templates): ?>
                                <optgroup label="Saved messages">
                                    <?php foreach ($templates as $templateRow): ?>
                                        <option value="<?= (int) $templateRow['id'] ?>"><?= e($templateRow['label']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    <textarea id="sms-text" name="message_content" x-model="text" required rows="9" maxlength="1000" autocomplete="off" class="form-input" placeholder="The exact text people should receive" @input="reviewing = false; reviewNote = ''"><?= e($values['message_content']) ?></textarea>
                    <label class="flex items-center gap-2 text-sm text-slate-600 mt-2" x-show="smsHasNames(numbers) || nameFirst()" x-cloak>
                        <input type="checkbox" :checked="nameFirst()" @change="setNameFirst($event.target.checked)">
                        Start each SMS with the person's name
                    </label>
                    <p class="sms-credit-bar" :class="estimate().short ? 'is-short' : ''" x-text="estimate().label"></p>
                    <p class="text-slate-500 text-xs mt-2" x-show="estimate().brackets">Replace the words in [brackets] before this can send. <button type="button" class="text-brand-400 bg-transparent border-0 cursor-pointer p-0" @click="text += (text && !text.endsWith(' ') ? ' ' : '') + '{name}'">Insert {name}</button></p>
                    <div class="sms-preview" x-show="text" x-cloak>
                        <p class="sms-preview-label">People will read</p>
                        <p x-text="estimate().preview"></p>
                    </div>
                </div>
                </div>
                <details class="sms-more text-sm text-slate-400" <?= ($err !== '' || $values['campaign_name'] !== '' || trim((string) $values['scheduled_at']) !== '') ? 'open' : '' ?>>
                    <summary class="sms-more-head">
                        <span class="sms-step-no is-soft">3</span>
                        <span class="sms-step-title">Name, who it is for, and send later</span>
                        <span class="sms-more-hint" x-text="when ? ('Sends ' + when.replace('T', ' ')) : 'Optional · sends now'"></span>
                    </summary>
                    <div class="grid md:grid-cols-3 gap-4 mt-3">
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Name this send</label>
                            <input type="text" name="campaign_name" maxlength="120" autocomplete="off" value="<?= e($values['campaign_name']) ?>" placeholder="AGM notice" class="form-input">
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Who it is for</label>
                            <select name="audience" required class="form-input">
                                <?php foreach ($audiences as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $audiencePick === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-medium mb-1.5">Why</label>
                            <select name="purpose" required class="form-input">
                                <?php foreach ($purposes as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $purposePick === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="max-w-sm mt-3">
                        <label class="block text-slate-400 text-xs font-medium mb-1.5">Nepal time</label>
                        <input type="datetime-local" name="scheduled_at" value="<?= e($values['scheduled_at']) ?>" class="form-input" x-model="when" @input="reviewing = false; reviewNote = ''">
                        <p class="text-slate-500 text-xs mt-1">Leave this empty to send now. Credits stay held until it sends.</p>
                    </div>
                </details>
                <?php
                $guardKey = 'send-sms';
                $declarationAccepted = (isset($_POST['send_sms'], $_POST['legal_accept']) && $_POST['legal_accept'] === '1' && $err !== '')
                    || (isset($_SESSION['sms_declared']) && (int) $_SESSION['sms_declared'] === $cid);
                $guardMath = false;
                require __DIR__ . '/../includes/use-declaration.php';
                ?>
                <div class="sms-review" x-show="reviewing" x-cloak>
                    <p class="sms-preview-label">Check once</p>
                    <dl class="sms-review-grid">
                        <div><dt>To</dt><dd x-text="estimate().count + ' numbers'"></dd></div>
                        <div><dt>Credits</dt><dd x-text="estimate().credits + ' · ' + (balance - estimate().credits).toLocaleString('en-IN') + ' left after'"></dd></div>
                        <div><dt>When</dt><dd x-text="when ? when.replace('T', ' ') + ' Nepal time' : 'Right now'"></dd></div>
                    </dl>
                    <p class="mt-3 whitespace-pre-wrap" x-text="estimate().preview"></p>
                </div>
                <div class="sms-actionbar">
                    <div class="sms-actionbar-sum">
                        <span><strong x-text="estimate().count"></strong> numbers</span>
                        <span><strong x-text="estimate().credits"></strong> credits</span>
                        <span class="sms-actionbar-left" :class="estimate().short ? 'is-short' : ''" x-text="(balance - estimate().credits).toLocaleString('en-IN') + ' left after'"></span>
                        <span class="sms-actionbar-note" x-show="reviewNote && !reviewing" x-cloak x-text="reviewNote"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="px-4 py-3 text-slate-400 text-sm" x-show="reviewing" x-cloak @click="reviewing = false">Back</button>
                        <button type="button" class="px-8 py-3 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl" x-show="!reviewing" @click="openReview()">Review</button>
                        <button type="submit" name="send_sms" class="px-8 py-3 bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold rounded-xl disabled:opacity-60" x-show="reviewing" x-cloak :disabled="!reviewing" :aria-busy="sending" :class="sending ? 'opacity-60 cursor-progress' : ''" x-text="sending ? 'Sending…' : (when ? ('Schedule ' + estimate().credits + ' SMS') : ('Send ' + estimate().credits + ' SMS'))">Send SMS</button>
                    </div>
                </div>
            </form>
            <details class="px-5 pb-5">
                <summary class="cursor-pointer text-sm text-slate-400">Save this message or number list for next time</summary>
            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                <form method="POST" class="flex gap-2" onsubmit="var send=document.getElementById('sms-send'); this.message_content.value=send.message_content.value; this.numbers.value=send.numbers.value;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="message_content" value="">
                    <input type="hidden" name="numbers" value="">
                    <input type="text" name="template_label" maxlength="60" required class="form-input" placeholder="Save message as">
                    <button type="submit" name="save_template" class="shrink-0 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-xl">Save</button>
                </form>
                <form method="POST" class="flex gap-2" onsubmit="var send=document.getElementById('sms-send'); this.message_content.value=send.message_content.value; this.numbers.value=send.numbers.value;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="message_content" value="">
                    <input type="hidden" name="numbers" value="">
                    <select name="list_kind" class="form-input max-w-[9rem]">
                        <option value="program">Program</option>
                        <option value="regular">Regular</option>
                    </select>
                    <input type="text" name="list_label" maxlength="60" required class="form-input" placeholder="AGM members, or Monthly interest">
                    <button type="submit" name="save_list" class="shrink-0 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs rounded-xl">Save list</button>
                </form>
            </div>
            <?php if ($templates || $numberLists): ?>
                <div class="px-5 pb-5 flex flex-wrap gap-2">
                    <?php foreach ($templates as $templateRow): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="template_id" value="<?= (int) $templateRow['id'] ?>">
                            <button type="submit" name="delete_template" class="text-xs text-slate-500 hover:text-red-400 bg-transparent border-0 cursor-pointer" onclick="return confirm('Delete this saved message?')">Delete <?= e($templateRow['label']) ?></button>
                        </form>
                    <?php endforeach; ?>
                    <?php foreach ($numberLists as $listRow): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="list_id" value="<?= (int) $listRow['id'] ?>">
                            <button type="submit" name="delete_list" class="text-xs text-slate-500 hover:text-red-400 bg-transparent border-0 cursor-pointer" onclick="return confirm('Delete this number list?')">Delete <?= e($listRow['label']) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </details>
        </div>
        <?php if ($route['choose_sender']): ?>
        <div class="lg:col-span-2 space-y-6 min-w-0">
                <div class="dash-panel">
                    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Sender name</h3></div>
                    <form method="POST" class="p-5 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <p class="text-slate-400 text-sm">Request the name people should see. It works after approval, and only if that name is registered on the SMS line.</p>
                        <input type="text" name="requested_sender" maxlength="11" placeholder="SAHAKARI" class="form-input" required>
                        <button type="submit" name="request_sender" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm rounded-xl transition">Request name</button>
                        <?php if ($senderRequests): ?>
                            <ul class="text-sm space-y-1">
                                <?php foreach ($senderRequests as $senderRow): ?>
                                    <li class="flex justify-between gap-3"><span class="text-white"><?= e($senderRow['sender_name']) ?></span><span class="text-slate-500"><?= e(ucfirst($senderRow['status'])) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </form>
                </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($scheduledRows): ?>
        <div class="dash-panel overflow-hidden mb-6">
            <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Scheduled</h3></div>
            <div class="divide-y divide-slate-800">
                <?php foreach ($scheduledRows as $sched): ?>
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm"><?= e($sched['campaign_name']) ?></p>
                            <p class="text-slate-500 text-xs"><?= e(sms_format_time($sched['scheduled_at'])) ?> Nepal time · <?= (int) $sched['recipients_count'] ?> numbers · credits stay held until this sends. Cancel returns them.</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= (int) $sched['id'] ?>">
                            <button type="submit" name="cancel_campaign" class="text-red-400 text-xs bg-transparent border-0 cursor-pointer" onclick="return confirm('Cancel this scheduled SMS?')">Cancel</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="dash-panel overflow-hidden mb-6">
        <div class="dash-panel-header flex items-center justify-between">
            <h3 class="font-heading font-semibold text-white">Recent SMS</h3>
            <a href="sms-logs.php" class="text-brand-400 text-xs">All logs</a>
        </div>
        <?php if ($recent): ?>
            <div class="divide-y divide-slate-800">
                <?php foreach ($recent as $row): ?>
                    <div class="px-5 py-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-white text-sm"><?= e($row['recipient']) ?> <span class="sms-state sms-state-<?= e(in_array($row['status'], array('sent', 'failed'), true) ? $row['status'] : 'wait') ?>"><?= e(ucfirst((string) $row['status'])) ?></span></p>
                            <p class="sms-log-clip text-slate-500 text-xs mt-1"><?= e($row['message_text']) ?></p>
                        </div>
                        <a href="sms-portal.php?reuse=<?= (int) $row['id'] ?>" class="shrink-0 text-brand-400 text-xs">Send again</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="p-5 text-slate-500 text-sm">No SMS sent from this account yet.</p>
        <?php endif; ?>
    </div>
    <?php if ($creditNotes): ?>
        <details class="dash-panel overflow-hidden mb-6">
            <summary class="dash-panel-header cursor-pointer"><h3 class="font-heading font-semibold text-white">Credits added</h3></summary>
            <div class="divide-y divide-slate-800">
                <?php foreach ($creditNotes as $creditNote): ?>
                    <div class="p-4 flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-slate-300 text-sm"><?= e($creditNote['note']) ?></p>
                        <p class="text-white text-sm"><?= number_format((int) $creditNote['credits']) ?> SMS<?= trim((string) $creditNote['reversed_at']) !== '' ? ' · Taken back' : '' ?> · <?= e(sms_format_time($creditNote['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
<?php endif; ?>
<link rel="stylesheet" href="../assets/css/sms-portal.css">
<script src="../assets/js/sms-portal.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
