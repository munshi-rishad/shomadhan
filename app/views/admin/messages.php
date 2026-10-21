<?php
// app/views/admin/messages.php - ?page=messages
// Chats with users (admin + employee: everyone sees every chat and can answer).
// Left: chats (waiting for an answer first), filter (all / unread / seen but not answered / ...) + search. Right: the open chat. Phone: list, or the chat full screen.
// Shown inside admin/layout.php (AdminController). Gets: $chats, $conv (open chat or null), $msgs, $filter, $q, $draft
// + the layout's $t, $lang, $base, $errors.

// ---- "When" of the last message: now / N min / time today / day + month ----
$ago = function ($ts) use ($t) {
    $s = time() - strtotime($ts);
    if ($s < 60) { return $t['msg_now']; }
    if ($s < 3600) { return sprintf($t['msg_min'], floor($s / 60)); }
    if (date('Y-m-d', strtotime($ts)) === date('Y-m-d')) { return date('h:i A', strtotime($ts)); }
    return date('d M', strtotime($ts));
};
$nm = $lang === 'en' ? 'en' : 'bn';
// Address of one filter chip, keeping the search
$fUrl = function ($f) use ($base, $q) {
    return $base . '?page=messages&f=' . $f . ($q !== '' ? '&q=' . rawurlencode($q) : '');
};
?>
<section class="msg adm-msg<?= $conv ? ' has-chat' : '' ?>">
    <?php /* ---- Left: title, search, filter chips, chat list ---- */ ?>
    <div class="msg-side">
        <h1 class="adm-h1"><?= e($t['adm_menu_messages']) ?></h1>
        <form class="msg-search" method="get" action="<?= e($base) ?>" role="search">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="hidden" name="page" value="messages"><input type="hidden" name="f" value="<?= e($filter) ?>">
            <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['msg_adm_search']) ?>" aria-label="<?= e($t['msg_adm_search']) ?>">
        </form>
        <nav class="msg-chips">
            <?php foreach (['unread', 'noreply'] as $f): /* press again = back to all chats */ ?>
                <a href="<?= e($fUrl($filter === $f ? 'all' : $f)) ?>"<?= $filter === $f ? ' class="on" aria-current="page"' : '' ?>><?= e($t['msg_f_' . $f]) ?><?php if (isset($chipCounts[$f])): ?><span class="mc-n<?= $chipCounts[$f] ? ' mc-hot' : '' ?>"><?= (int)$chipCounts[$f] ?></span><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if (!$chats): ?><p class="empty"><?= e($q !== '' ? $t['msg_none_found'] : $t['msg_adm_none']) ?></p><?php endif; ?>
        <ul class="msg-list">
            <?php foreach ($chats as $c): ?>
            <li><a class="msg-item<?= $conv && (int)$conv['id'] === (int)$c['id'] ? ' on' : '' ?>" href="<?= e($base) ?>?page=messages&amp;f=<?= e($filter) ?>&amp;c=<?= (int)$c['id'] ?><?= $q !== '' ? '&amp;q=' . e(rawurlencode($q)) : '' ?>">
                <?php view('partials/avatar', ['name' => $c['user_name'], 'photo' => $c['user_photo'], 'class' => 'avatar ch-av']); ?>
                <span class="msg-item-txt">
                    <span class="msg-item-top"><b><?= e($c['user_name']) ?></b><time><?= e($ago($c['last_message_at'])) ?></time></span>
                    <span class="msg-item-last"><?= e(($c['last_sender'] === 'staff' ? $t['msg_office_short'] . ': ' : '') . $c['last_body']) ?></span>
                    <span class="msg-item-chips">
                        <?php if ($c['booking_id'] === null): ?><span class="chip"><?= e($t['msg_f_support']) ?></span>
                        <?php else: ?><span class="chip">#<?= e(BookingModel::code($c['booking_id'])) ?></span><span class="chip"><?= e($c['name_' . $nm] ?: $c['name_bn']) ?></span><?php endif; ?>
                        <?php if ($c['unread_staff'] > 0): ?><span class="msg-n"><?= (int)$c['unread_staff'] ?></span>
                        <?php elseif ($c['last_sender'] === 'user'): /* seen, but the user's message is still the last one */ ?><span class="chip chip-wait"><?= e($t['msg_wait_reply']) ?></span><?php endif; ?>
                    </span>
                </span>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php /* ---- Right: the open chat, or "pick a chat" ---- */ ?>
    <div class="msg-pane">
        <?php if ($conv): ?>
            <?php view('partials/chat-thread', [
                't'      => $t,
                'lang'   => $lang,
                'conv'   => $conv,
                'msgs'   => $msgs,
                'side'   => 'staff',
                'action' => $base . '?page=messages&c=' . (int)$conv['id'],
                'back'   => $base . '?page=messages&f=' . $filter,
                'errors' => $errors,
                'draft'  => $draft,
                'live'   => $base . '?page=messages&c=' . (int)$conv['id'] . '&live=1',
            ]); ?>
        <?php else: ?>
            <div class="chat-pick"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg><p><?= e($t['msg_pick']) ?></p></div>
        <?php endif; ?>
    </div>
</section>
