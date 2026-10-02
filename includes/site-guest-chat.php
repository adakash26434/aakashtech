<?php
$guestChats = isset($guestChats) && is_array($guestChats) ? $guestChats : array();
$guestClass = isset($guestClass) ? (string) $guestClass : 'guest-chat';
if (!$guestChats) {
    return;
}
?>
<div class="<?= site_escape($guestClass) ?>">
    <?php foreach ($guestChats as $channel): ?>
        <a class="guest-chat-link guest-chat-link--<?= site_escape($channel['key']) ?>" href="<?= site_escape($channel['href']) ?>" target="_blank" rel="noopener noreferrer">
            <?= $channel['icon'] ?>
            <span><?= site_escape($channel['label']) ?></span>
        </a>
    <?php endforeach; ?>
</div>
