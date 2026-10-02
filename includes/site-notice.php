<?php
$noticeSettings = (isset($publicSite) && is_array($publicSite)) ? $publicSite : site_public_defaults();
$noticeOn = isset($noticeSettings['notice_enabled']) && $noticeSettings['notice_enabled'] === '1';
$noticeTitle = isset($noticeSettings['notice_title']) ? trim($noticeSettings['notice_title']) : '';
$noticeBody = isset($noticeSettings['notice_body']) ? trim($noticeSettings['notice_body']) : '';
$noticeLink = site_public_url(isset($noticeSettings['notice_link']) ? $noticeSettings['notice_link'] : '');
$noticeLabel = isset($noticeSettings['notice_link_label']) ? trim($noticeSettings['notice_link_label']) : '';
if ($noticeLabel === '') {
    $noticeLabel = 'Open';
}
$noticeImage = site_notice_file(isset($noticeSettings['notice_image']) ? $noticeSettings['notice_image'] : '');
$noticeKey = substr(hash('sha256', $noticeTitle . "\0" . $noticeBody . "\0" . $noticeLink . "\0" . $noticeImage), 0, 12);
$noticeAlt = $noticeTitle !== '' ? $noticeTitle : 'Notice';
?>
<?php if ($noticeOn && ($noticeBody !== '' || $noticeImage !== '')): ?>
<div class="site-notice" id="site-notice" data-notice-key="<?= site_escape($noticeKey) ?>" hidden>
    <div class="site-notice-card<?= $noticeImage !== '' ? ' site-notice-card--image' : '' ?>" role="dialog" aria-modal="true" aria-labelledby="site-notice-title">
        <?php if ($noticeImage !== ''): ?>
            <img class="site-notice-image" src="<?= site_escape($noticeImage) ?>" alt="<?= site_escape($noticeAlt) ?>">
        <?php endif; ?>
        <div class="site-notice-copy">
        <p class="site-notice-kicker">Notice</p>
        <h2 id="site-notice-title" class="font-heading"><?= site_escape($noticeTitle !== '' ? $noticeTitle : 'Notice') ?></h2>
        <?php if ($noticeBody !== ''): ?>
            <p><?= nl2br(site_escape($noticeBody), false) ?></p>
        <?php endif; ?>
        <div class="site-notice-actions">
            <?php if ($noticeLink !== ''): ?>
                <a class="button button--primary" href="<?= site_escape($noticeLink) ?>" target="_blank" rel="noopener noreferrer"><?= site_escape($noticeLabel) ?></a>
            <?php endif; ?>
            <button type="button" class="button button--outline" data-notice-close>Close</button>
        </div>
        </div>
    </div>
</div>
<?php endif; ?>
