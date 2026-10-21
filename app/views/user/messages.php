<?php
// app/views/user/messages.php - messages.php
// My chats with the office. PC: list left + open chat right. Phone: the list, or the open chat full screen.
// The support chat is the green card on top (not repeated in the list); the list holds one chat per booking.
// Pulled into the user page frame (user/home.php, $mode = 'messages') by MessageController.
// Gets: $chats, $conv (open chat or null), $msgs, $filter, $fCounts (chats per filter), $mq (search), $errors, $draft
//       + the frame's $t, $lang.

// ---- "When" of the last message: now / N min / time today / day + month ----
$ago = function ($ts) use ($t) {
    $s = time() - strtotime($ts);
    if ($s < 60) { return $t['msg_now']; }
    if ($s < 3600) { return sprintf($t['msg_min'], floor($s / 60)); }
    if (date('Y-m-d', strtotime($ts)) === date('Y-m-d')) { return date('h:i A', strtotime($ts)); }
    return date('d M', strtotime($ts));
};
// ---- Split the support chat (no booking) from the booking chats ----
$support = null;
$list = [];
foreach ($chats as $c) {
    if ($c['booking_id'] === null) {
        $support = $c;
    } else {
        $list[] = $c;
    }
}
// Filter chip icons, and the address of one chip (keeps the search)
$fIc = [
    'all'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
    'support' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>',
    'active'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'done'    => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
];
$filterUrl = function ($f) use ($mq) {
    return 'messages.php?f=' . $f . ($mq !== '' ? '&mq=' . rawurlencode($mq) : '');
};
?>
<section class="sec u-page-sec msg<?= $conv ? ' has-chat' : '' ?>">
    <div class="u-head"><h1><?= e($t['msg_title']) ?></h1><p><?= e($t['msg_sub']) ?></p></div>
    <?php /* ---- Filters (with how many chats each has) + search ---- */ ?>
    <div class="msg-bar">
        <nav class="msg-chips" aria-label="<?= e($t['msg_title']) ?>">
            <?php foreach (MessageModel::FILTERS as $f): ?>
                <a href="<?= e($filterUrl($f)) ?>"<?= $filter === $f ? ' class="on" aria-current="page"' : '' ?>><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $fIc[$f] ?></svg><?= e($t['msg_f_' . $f]) ?><span class="mc-n"><?= (int)$fCounts[$f] ?></span></a>
            <?php endforeach; ?>
        </nav>
        <form class="msg-search" method="get" action="messages.php" role="search">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="hidden" name="f" value="<?= e($filter) ?>">
            <input type="search" name="mq" value="<?= e($mq) ?>" maxlength="60" placeholder="<?= e($t['msg_search_ph']) ?>" aria-label="<?= e($t['msg_search_ph']) ?>">
        </form>
    </div>

    <?php /* ---- Left: support card + booking chats ---- */ ?>
    <div class="msg-side" data-live="msg-side">
        <?php /* support card: opens my support chat, or starts one (?support=1) when there is none yet */ ?>
        <?php if (in_array($filter, ['all', 'support'], true) && ($mq === '' || $support)): ?>
        <a class="msg-sup<?= $conv && $support && (int)$conv['id'] === (int)$support['id'] ? ' on' : '' ?>" href="<?= $support ? 'messages.php?c=' . (int)$support['id'] : 'messages.php?support=1' ?>">
            <span class="ch-av ch-sup"><?php view('partials/support-icon'); ?></span>
            <span class="msg-sup-txt"><b><?= e($t['msg_support']) ?></b>
                <small><?= e($support ? (($support['last_sender'] === 'user' ? $t['msg_you'] . ': ' : '') . $support['last_body']) : $t['msg_support_sub']) ?></small></span>
            <?php if ($support && $support['unread_user'] > 0): ?><span class="msg-n"><?= (int)$support['unread_user'] ?></span><?php endif; ?>
            <span class="msg-sup-go"><?= e($t['msg_chat_now']) ?></span>
        </a>
        <?php endif; ?>

        <?php if ($filter !== 'support'): ?>
        <h2 class="msg-h"><?= e($t['msg_recent']) ?></h2>
        <?php if (!$list): ?><p class="muted msg-none"><?= e($mq !== '' ? $t['msg_none_found'] : $t['msg_none']) ?></p><?php endif; ?>
        <ul class="msg-list">
            <?php foreach ($list as $c): $svc = $lang === 'en' ? ($c['name_en'] ?: $c['name_bn']) : $c['name_bn']; ?>
            <li><a class="msg-item<?= $conv && (int)$conv['id'] === (int)$c['id'] ? ' on' : '' ?>" href="messages.php?c=<?= (int)$c['id'] ?>">
                <span class="ch-av tile-<?= e($c['service_color']) ?>"><img loading="lazy" decoding="async" src="<?= e($c['service_icon']) ?>" alt=""></span>
                <span class="msg-item-txt">
                    <span class="msg-item-top"><b><?= e($svc) ?></b><time><?= e($ago($c['last_message_at'])) ?></time></span>
                    <span class="msg-item-last"><?= e(($c['last_sender'] === 'user' ? $t['msg_you'] . ': ' : '') . $c['last_body']) ?></span>
                    <span class="msg-item-chips"><span class="chip">#<?= e(BookingModel::code($c['booking_id'])) ?></span><span class="bk-pill bp-<?= e($c['booking_status']) ?>"><?= e($t['bk_st_' . $c['booking_status']]) ?></span>
                        <?php if ($c['unread_user'] > 0): ?><span class="msg-n"><?= (int)$c['unread_user'] ?></span><?php endif; ?></span>
                </span>
            </a></li>
            <?php endforeach; ?>
        </ul>
        <p class="msg-tip"><?= e($t['msg_tip']) ?> <a href="bookings.php?tab=ongoing"><?= e($t['bk_my']) ?></a></p>
        <?php endif; ?>
    </div>

    <?php /* ---- Right: the open chat, or "pick a chat" ---- */ ?>
    <div class="msg-pane">
        <?php if ($conv): ?>
            <?php view('partials/chat-thread', [
                't'      => $t,
                'lang'   => $lang,
                'conv'   => $conv,
                'msgs'   => $msgs,
                'side'   => 'user',
                'action' => 'messages.php?c=' . (int)$conv['id'],
                'back'   => 'messages.php',
                'errors' => $errors,
                'draft'  => $draft,
                'live'   => 'messages.php?c=' . (int)$conv['id'] . '&live=1',
            ]); ?>
        <?php else: ?>
            <div class="chat-pick"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg><p><?= e($t['msg_pick']) ?></p></div>
        <?php endif; ?>
    </div>
</section>
