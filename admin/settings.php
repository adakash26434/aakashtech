<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require __DIR__ . '/includes/settings-actions.php';
?>
<div class="mb-8">
    <h1 class="font-heading font-bold text-white text-2xl mb-1">Settings</h1>
        <p class="text-slate-500 text-sm">Logo, public contact details, the homepage lines, and the inbox that receives new requests. <a class="text-brand-400" href="manual.php#settings">नेपाली चरण</a></p>
</div>

<?php if ($msg): ?>
    <div class="mb-4 p-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm"><?= e($err) ?></div>
<?php endif; ?>

<?php
$settingsTab = 'mail';
if (isset($_POST['save_homepage'])) {
    $settingsTab = 'home';
} elseif (isset($_POST['save_legal'])) {
    $settingsTab = 'legal';
} elseif (isset($_POST['save_ai'])) {
    $settingsTab = 'assistant';
} elseif (isset($_POST['update_settings'])) {
    $settingsTab = 'site';
} elseif (isset($_POST['change_password']) || isset($_POST['totp_action'])) {
    $settingsTab = 'security';
}
?>
<div x-data="{ tab: '<?= e($settingsTab) ?>' }">
<div class="portal-tabs" role="tablist">
    <button type="button" @click="tab='mail'" :class="tab==='mail' ? 'is-on' : ''">Mail</button>
    <button type="button" @click="tab='legal'" :class="tab==='legal' ? 'is-on' : ''">Privacy</button>
    <button type="button" @click="tab='assistant'" :class="tab==='assistant' ? 'is-on' : ''">Assistant</button>
    <button type="button" @click="tab='home'" :class="tab==='home' ? 'is-on' : ''">Homepage</button>
    <button type="button" @click="tab='site'" :class="tab==='site' ? 'is-on' : ''">Site</button>
    <button type="button" @click="tab='security'" :class="tab==='security' ? 'is-on' : ''">Security</button>
</div>
<div x-show="tab==='mail'">
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Request emails</h3></div>
    <form method="POST" action="" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">When a contact message, service order, domain request, paid domain, wallet top-up, identity check, or support ticket arrives, this address gets an email. You do not have to stay signed in to notice it.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="notify_email">Notification email</label>
            <input id="notify_email" type="email" name="notify_email" maxlength="120" class="form-input" value="<?= e($notifyEmail) ?>" placeholder="info@aakashtechnologies.com.np">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="mail_from">Sending address</label>
            <input id="mail_from" type="email" name="mail_from" maxlength="120" class="form-input" value="<?= e($mailFrom) ?>" placeholder="noreply@aakashtechnologies.com.np">
        </div>
        <p class="text-slate-500 text-xs">Queries and notices arrive at the notification email. Client mail is sent from the sending address. The default is noreply@aakashtechnologies.com.np. A reply goes to the public email below. On cPanel, that sending address should be a mailbox on this domain. Leave the notification email blank to stop admin notices. Client mail still goes out, and the requests still appear in the admin panel.</p>
        <?php if ($notifyTestAt !== ''): ?>
            <p class="text-slate-400 text-sm">Last test <?= e($notifyTestAt) ?>: <?= $notifyTestResult === 'accepted' ? 'the server accepted it. Confirm it is in the inbox.' : 'the server refused it.' ?><?= $notifyTestError !== '' ? ' ' . e($notifyTestError) : '' ?></p>
        <?php endif; ?>
        <?php if ($notifyLastAt !== ''): ?>
            <p class="text-slate-400 text-sm">Last request email <?= e($notifyLastAt) ?>: <?= $notifyLastResult === 'accepted' ? 'the server accepted it.' : 'the server refused it.' ?></p>
        <?php endif; ?>
        <div class="flex flex-wrap gap-3">
            <button type="submit" name="save_notify" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save notification email</button>
            <button type="submit" name="send_notify_test" value="1" class="px-6 py-2.5 border border-slate-600 text-white text-sm font-medium rounded-xl transition">Send a test email</button>
        </div>
    </form>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Mail the client receives</h3></div>
    <div class="p-5 space-y-4">
        <p class="text-slate-400 text-sm">These go out on their own as <?= e(site_sender_name()) ?> &lt;<?= e($mailFrom) ?>&gt;. The password is never written in the email.</p>
        <?php foreach (billing_mail_catalog() as $draft): ?>
            <div class="rounded-xl border border-slate-800 p-4">
                <p class="text-white text-sm font-medium"><?= e($draft['when']) ?></p>
                <p class="text-brand-300 text-xs mt-2">Subject: <?= e($draft['subject']) ?></p>
                <p class="text-slate-400 text-xs mt-2 whitespace-pre-wrap"><?= e(implode("\n", $draft['lines'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</div>
<div x-show="tab==='legal'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Privacy, cookies, and terms</h3></div>
    <form method="POST" action="" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">These pages are linked from the public footer. The prepared text follows the Individual Privacy Act, 2075 and the Individual Privacy Regulation, 2077, and it describes the one sign-in cookie this site sets. Edit the wording here. Tick restore to put the prepared text back.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="privacy_policy">Privacy policy</label>
            <textarea id="privacy_policy" name="privacy_policy" maxlength="12000" rows="12" class="form-input"><?= e(site_legal_text($settings, 'privacy_policy')) ?></textarea>
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="restore_privacy" value="1"> Restore the prepared privacy policy
            </label>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="cookie_policy">Cookie notice</label>
            <textarea id="cookie_policy" name="cookie_policy" maxlength="12000" rows="8" class="form-input"><?= e(site_legal_text($settings, 'cookie_policy')) ?></textarea>
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="restore_cookies" value="1"> Restore the prepared cookie notice
            </label>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="terms_of_service">Terms of service</label>
            <textarea id="terms_of_service" name="terms_of_service" maxlength="12000" rows="10" class="form-input"><?= e(site_legal_text($settings, 'terms_of_service')) ?></textarea>
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="restore_terms" value="1"> Restore the prepared terms
            </label>
        </div>
        <button type="submit" name="save_legal" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save privacy, cookies, and terms</button>
    </form>
</div>

</div>
<div x-show="tab==='assistant'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Public assistant</h3></div>
    <form method="POST" action="" class="p-5 space-y-4" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">Visitors can ask about the services from the public pages. The assistant reads those pages only. It is not given passwords, portal logins, wallet records, identity files, or payment account numbers.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_provider">Which key to use</label>
            <select id="ai_provider" name="ai_provider" class="form-input">
                <option value="" <?= $aiProvider === '' ? 'selected' : '' ?>>Off</option>
                <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>>Gemini</option>
                <option value="deepseek" <?= $aiProvider === 'deepseek' ? 'selected' : '' ?>>DeepSeek</option>
            </select>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_gemini_key">Gemini key</label>
            <input id="ai_gemini_key" type="password" name="ai_gemini_key" maxlength="200" class="form-input" value="" autocomplete="new-password" placeholder="<?= $geminiSaved ? 'A key is saved. Paste a new one to replace it.' : 'Paste the Gemini key' ?>">
            <?php if ($geminiSaved): ?>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="clear_gemini" value="1"> Remove the saved Gemini key
                </label>
            <?php endif; ?>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="ai_deepseek_key">DeepSeek key</label>
            <input id="ai_deepseek_key" type="password" name="ai_deepseek_key" maxlength="200" class="form-input" value="" autocomplete="new-password" placeholder="<?= $deepseekSaved ? 'A key is saved. Paste a new one to replace it.' : 'Paste the DeepSeek key' ?>">
            <?php if ($deepseekSaved): ?>
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="clear_deepseek" value="1"> Remove the saved DeepSeek key
                </label>
            <?php endif; ?>
        </div>
        <p class="text-slate-500 text-xs">The key stays on the server. It is not shown again and it is not printed on the website. Gemini uses gemini-2.5-flash. DeepSeek uses deepseek-chat. Choose Off to hide the assistant.</p>
        <button type="submit" name="save_ai" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save assistant</button>
    </form>
</div>
</div>
<div x-show="tab==='home'" x-cloak>
<div class="dash-panel mb-6">
    <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Homepage</h3></div>
    <form method="POST" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <p class="text-slate-400 text-sm">These lines are the public front page. The title uses one line per row. Service cards and prices stay under Services.</p>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_eyebrow">Small line above the title</label>
            <input id="home_eyebrow" name="home_eyebrow" maxlength="180" required class="form-input" value="<?= e($settings['home_eyebrow']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_title">Title, one line per row</label>
            <textarea id="home_title" name="home_title" maxlength="240" rows="3" required class="form-input"><?= e($settings['home_title']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_lede">Opening paragraph</label>
            <textarea id="home_lede" name="home_lede" maxlength="600" rows="3" required class="form-input"><?= e($settings['home_lede']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_ribbon">Strip under the title</label>
            <input id="home_ribbon" name="home_ribbon" maxlength="180" required class="form-input" value="<?= e($settings['home_ribbon']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_services_kicker">Services label</label>
            <input id="home_services_kicker" name="home_services_kicker" maxlength="80" required class="form-input" value="<?= e($settings['home_services_kicker']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_services_heading">Services heading</label>
            <input id="home_services_heading" name="home_services_heading" maxlength="160" required class="form-input" value="<?= e($settings['home_services_heading']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_services_text">Services paragraph</label>
            <textarea id="home_services_text" name="home_services_text" maxlength="800" rows="3" required class="form-input"><?= e($settings['home_services_text']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_about_heading">About heading</label>
            <input id="home_about_heading" name="home_about_heading" maxlength="180" required class="form-input" value="<?= e($settings['home_about_heading']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_about_text">About paragraph</label>
            <textarea id="home_about_text" name="home_about_text" maxlength="800" rows="3" required class="form-input"><?= e($settings['home_about_text']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_process_heading">How we work heading</label>
            <input id="home_process_heading" name="home_process_heading" maxlength="180" required class="form-input" value="<?= e($settings['home_process_heading']) ?>">
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_process_text">How we work paragraph</label>
            <textarea id="home_process_text" name="home_process_text" maxlength="800" rows="3" required class="form-input"><?= e($settings['home_process_text']) ?></textarea>
        </div>
        <div>
            <label class="block text-slate-400 text-xs font-medium mb-1.5" for="home_contact_heading">Contact heading</label>
            <input id="home_contact_heading" name="home_contact_heading" maxlength="180" required class="form-input" value="<?= e($settings['home_contact_heading']) ?>">
        </div>
        <button type="submit" name="save_homepage" value="1" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save homepage</button>
    </form>
</div>
</div>
<div x-show="tab==='site'" x-cloak>
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Site Settings -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Site Information</h3></div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php $logoPreview = site_logo_web_path($settings['logo_path'] ?? ''); ?>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Logo</label>
                <?php if ($logoPreview !== ''): ?>
                    <img src="<?= e($logoPreview) ?>" alt="Current logo" class="mb-3 h-14 w-14 rounded-xl object-contain bg-white p-1">
                <?php endif; ?>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                <p class="text-slate-500 text-xs mt-1.5">PNG, JPG, WEBP, or GIF. Up to 2 MB. Shown in the header, footer, and both portals.</p>
                <?php if ($logoPreview !== ''): ?>
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remove_logo" value="1"> Remove the current logo
                    </label>
                <?php endif; ?>
            </div>
            <?php $faviconPreview = site_favicon_file($settings['favicon_path'] ?? ''); ?>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5" for="favicon">Tab icon (shown in the browser tab and bookmarks)</label>
                <div class="flex items-center gap-4 mb-3">
                    <?php if ($faviconPreview !== ''): ?>
                        <img src="../<?= e($faviconPreview) ?>?v=<?= (int) @filemtime(dirname(__DIR__) . '/' . $faviconPreview) ?>" alt="Current tab icon" width="32" height="32" class="h-8 w-8 rounded bg-white object-contain" style="border:1px solid #dce8e4">
                        <span class="text-slate-400 text-sm">This is how it looks in a tab. Visitors may need to refresh or reopen the browser to see a new one.</span>
                    <?php else: ?>
                        <span class="text-slate-400 text-sm">None uploaded. The logo is used for now.</span>
                    <?php endif; ?>
                </div>
                <input id="favicon" type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/x-icon,.ico" class="form-input">
                <p class="text-slate-500 text-xs mt-1.5">A square PNG works best: 512 by 512 pixels, simple, with a clear shape. Also PNG, JPG, WEBP or ICO, up to 500 KB.</p>
                <?php if ($faviconPreview !== ''): ?>
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remove_favicon" value="1"> Remove it and use the logo instead
                    </label>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Site name</label>
                <input type="text" name="site_name" maxlength="80" class="form-input" value="<?= e($settings['site_name'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Public email</label>
                <input type="email" name="site_email" maxlength="120" class="form-input" value="<?= e($settings['site_email'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">WhatsApp number</label>
                <input type="text" name="whatsapp_number" maxlength="40" class="form-input" value="<?= e($settings['whatsapp_number'] ?? '') ?>" placeholder="10-digit mobile for the chat link">
                <p class="text-slate-500 text-xs mt-1.5">Used only to open WhatsApp. The number is not printed on the site. A 10-digit Nepal mobile is sent as 977…</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Viber number</label>
                <input type="text" name="viber_number" maxlength="40" class="form-input" value="<?= e($settings['viber_number'] ?? '') ?>" placeholder="10-digit mobile for the chat link">
                <p class="text-slate-500 text-xs mt-1.5">Used only to open Viber. The number is not printed on the site.</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Messenger link</label>
                <input type="url" name="messenger_url" maxlength="200" class="form-input" value="<?= e($settings['messenger_url'] ?? '') ?>" placeholder="https://m.me/your-page">
                <p class="text-slate-500 text-xs mt-1.5">Paste the page’s https://m.me/ link. It is not invented here.</p>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Location</label>
                <input type="text" name="site_location" maxlength="120" class="form-input" value="<?= e($settings['site_location'] ?? '') ?>">
            </div>
            <div class="sm:col-span-2">
                <p class="text-slate-300 text-sm font-medium mb-1">Company details shown in the site footer</p>
                <p class="text-slate-500 text-xs mb-3">Visitors trust a business that shows who it is. Fill in only what is true; empty lines are not shown.</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="block text-slate-400 text-xs font-medium mb-1.5" for="company_legal_name">Registered name</label>
                        <input id="company_legal_name" type="text" name="company_legal_name" maxlength="120" class="form-input" value="<?= e($settings['company_legal_name'] ?? '') ?>" placeholder="Aakash Technologies Pvt. Ltd."></div>
                    <div><label class="block text-slate-400 text-xs font-medium mb-1.5" for="company_registration">Company registration number</label>
                        <input id="company_registration" type="text" name="company_registration" maxlength="40" class="form-input" value="<?= e($settings['company_registration'] ?? '') ?>"></div>
                    <div><label class="block text-slate-400 text-xs font-medium mb-1.5" for="company_pan">PAN / VAT number</label>
                        <input id="company_pan" type="text" name="company_pan" maxlength="40" inputmode="numeric" class="form-input" value="<?= e($settings['company_pan'] ?? '') ?>"></div>
                    <div><label class="block text-slate-400 text-xs font-medium mb-1.5" for="office_hours">Office hours</label>
                        <input id="office_hours" type="text" name="office_hours" maxlength="120" class="form-input" value="<?= e($settings['office_hours'] ?? '') ?>" placeholder="Sun to Fri, 10:00 to 17:00"></div>
                </div>
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Line under the logo</label>
                <input type="text" name="footer_tagline" maxlength="180" class="form-input" value="<?= e($settings['footer_tagline'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Footer note</label>
                <input type="text" name="footer_text" maxlength="180" class="form-input" value="<?= e($settings['footer_text'] ?? '') ?>">
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Popup notice</h4>
                <label class="flex items-center gap-2 text-sm text-slate-300 mb-3">
                    <input type="checkbox" name="notice_enabled" value="1" <?= (($settings['notice_enabled'] ?? '') === '1') ? 'checked' : '' ?>>
                    Show this notice when someone opens the public site
                </label>
                <div class="space-y-3">
                    <input type="text" name="notice_title" maxlength="80" class="form-input" placeholder="Title" value="<?= e($settings['notice_title'] ?? '') ?>">
                    <textarea name="notice_body" maxlength="500" rows="3" class="form-input" placeholder="The notice visitors should read"><?= e($settings['notice_body'] ?? '') ?></textarea>
                    <input type="url" name="notice_link" maxlength="200" class="form-input" placeholder="Optional https:// link" value="<?= e($settings['notice_link'] ?? '') ?>">
                    <input type="text" name="notice_link_label" maxlength="40" class="form-input" placeholder="Link label, such as Read more" value="<?= e($settings['notice_link_label'] ?? '') ?>">
                    <?php $noticePreview = site_notice_file($settings['notice_image'] ?? ''); ?>
                    <?php if ($noticePreview !== ''): ?>
                        <img src="../<?= e($noticePreview) ?>" alt="Current notice image" class="w-full max-h-40 object-contain rounded-xl bg-white">
                    <?php endif; ?>
                    <input type="file" name="notice_image" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input">
                    <?php if ($noticePreview !== ''): ?>
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="remove_notice_image" value="1"> Remove the notice image
                        </label>
                    <?php endif; ?>
                </div>
                <p class="text-slate-500 text-xs mt-2">The image is optional. PNG, JPG, WEBP, or GIF, up to 2 MB. Turn the notice off when it is no longer needed. Closing it hides that same notice until the browser is opened again, or until you change the text or image.</p>
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Social contact</h4>
                <div class="space-y-3">
                    <input type="url" name="facebook_url" maxlength="200" class="form-input" placeholder="Facebook https:// link" value="<?= e($settings['facebook_url'] ?? '') ?>">
                    <input type="url" name="instagram_url" maxlength="200" class="form-input" placeholder="Instagram https:// link" value="<?= e($settings['instagram_url'] ?? '') ?>">
                    <input type="url" name="youtube_url" maxlength="200" class="form-input" placeholder="YouTube https:// link" value="<?= e($settings['youtube_url'] ?? '') ?>">
                    <input type="url" name="tiktok_url" maxlength="200" class="form-input" placeholder="TikTok https:// link" value="<?= e($settings['tiktok_url'] ?? '') ?>">
                    <input type="url" name="linkedin_url" maxlength="200" class="form-input" placeholder="LinkedIn https:// link" value="<?= e($settings['linkedin_url'] ?? '') ?>">
                </div>
                <p class="text-slate-500 text-xs mt-2">Only filled links appear as icons in the footer. WhatsApp, Viber, and Messenger use the fields above. A personal mobile is not shown as a call number.</p>
            </div>
            <div class="pt-2 border-t border-slate-800">
                <h4 class="font-heading font-semibold text-white text-sm mb-3">Online payment</h4>
                <div class="space-y-3">
                    <input type="text" name="esewa_id" maxlength="40" class="form-input" placeholder="eSewa ID" value="<?= e($settings['esewa_id'] ?? '') ?>" aria-label="eSewa ID">
                    <input type="text" name="khalti_id" maxlength="40" class="form-input" placeholder="Khalti ID" value="<?= e($settings['khalti_id'] ?? '') ?>" aria-label="Khalti ID">
                </div>
                <p class="text-slate-500 text-xs mt-2">eSewa and Khalti appear for the client only after an ID is saved here. A blank field keeps the value from cpanel-config.php.</p>
                <h4 class="font-heading font-semibold text-white text-sm mt-5 mb-3">Manual payment</h4>
                <textarea name="bank_details" maxlength="400" rows="3" class="form-input" placeholder="Bank name, account name, and account number" aria-label="Bank details"><?= e($settings['bank_details'] ?? '') ?></textarea>
                <p class="text-slate-500 text-xs mt-2">Bank transfer appears only after these details are saved. Leave it blank to hide manual payment.</p>
            </div>
            <button type="submit" name="update_settings" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Save public details</button>
        </form>
    </div>

</div>
</div>
<div x-show="tab==='security'" x-cloak>
<div class="grid lg:grid-cols-2 gap-6">
    <!-- Change Password -->
    <div class="dash-panel">
        <div class="dash-panel-header"><h3 class="font-heading font-semibold text-white">Change Password</h3></div>
        <form method="POST" action="" class="p-5 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Current Password</label>
                <input type="password" name="current_pass" required autocomplete="current-password" class="form-input" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">New Password</label>
                <input type="password" name="new_pass" required autocomplete="new-password" class="form-input" placeholder="•••••••••">
            </div>
            <div>
                <label class="block text-slate-400 text-xs font-medium mb-1.5">Confirm New Password</label>
                <input type="password" name="confirm_pass" required autocomplete="new-password" class="form-input" placeholder="•••••••••">
            </div>
            <button type="submit" name="change_password" class="px-6 py-2.5 bg-brand-500 hover:bg-brand-400 text-white text-sm font-medium rounded-xl transition">Change Password</button>
        </form>
    </div>
    <?php require __DIR__ . '/../includes/totp-manage-card.php'; ?>
</div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
