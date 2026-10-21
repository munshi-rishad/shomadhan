<?php
// app/views/partials/chat-thread.php
// One open chat: head, messages, send box. The same markup for the user (user/messages.php)
// and the office (admin/messages.php); only $side changes what the head shows.
// Needs: $t, $lang, $conv (MessageModel row), $msgs, $side ('user' | 'staff'), $action (form address),
//        $back (chat list address), $errors, $draft, $live (address for the live check; chat.js adds &after=ID)

// A support chat has no booking. A booking chat shows the service name in the chosen language.
$isSupport = $conv['booking_id'] === null;
$svc = $isSupport ? '' : ($lang === 'en' ? ($conv['name_en'] ?: $conv['name_bn']) : $conv['name_bn']);
?>
<div class="chat" id="chat" data-live="<?= e($live) ?>" data-last="<?= $msgs ? (int)end($msgs)['id'] : 0 ?>">
    <?php /* ---- Chat head: back arrow, who I am talking to, booking chips ---- */ ?>
    <div class="chat-head">
        <a class="chat-back" href="<?= e($back) ?>" aria-label="<?= e($t['login_back']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></a>
        <?php if ($side === 'staff'): /* office sees the user */ ?>
            <?php view('partials/avatar', ['name' => $conv['user_name'], 'photo' => $conv['user_photo'], 'class' => 'avatar ch-av']); ?>
            <div class="chat-title"><b><?= e($conv['user_name']) ?></b><small><a href="tel:+<?= e(phone_intl($conv['user_mobile'])) ?>"><?= e($conv['user_mobile']) ?></a></small></div>
        <?php elseif ($isSupport): /* user, support chat */ ?>
            <span class="ch-av ch-sup"><?php view('partials/support-icon'); ?></span>
            <div class="chat-title"><b><?= e($t['msg_support']) ?></b><small><?= e($t['msg_support_sub']) ?></small></div>
        <?php else: /* user, chat about one booking */ ?>
            <span class="ch-av tile-<?= e($conv['service_color']) ?>"><img src="<?= e($conv['service_icon']) ?>" alt=""></span>
            <div class="chat-title"><b><?= e($svc) ?></b><small><?= e($t['msg_office']) ?></small></div>
        <?php endif; ?>
        <div class="chat-chips">
            <?php if (!$isSupport): ?>
                <span class="chip">#<?= e(BookingModel::code($conv['booking_id'])) ?></span>
                <?php if ($side === 'staff'): ?><span class="chip"><?= e($svc) ?></span><?php endif; ?>
                <span class="bk-pill bp-<?= e($conv['booking_status']) ?>"><?= e($t['bk_st_' . $conv['booking_status']]) ?></span>
            <?php elseif ($side === 'staff'): ?>
                <span class="chip"><?= e($t['msg_support']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php /* ---- User only: link card to the booking this chat is about ---- */ ?>
    <?php if ($side === 'user' && !$isSupport): ?>
    <a class="chat-bk" href="bookings.php?tab=<?= e($conv['booking_status']) ?>#bk-<?= (int)$conv['booking_id'] ?>">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/></svg>
        <span><b><?= e($t['bk_id']) ?>: #<?= e(BookingModel::code($conv['booking_id'])) ?></b><small><?= e($svc) ?> · <?= e($t['bk_st_' . $conv['booking_status']]) ?></small></span>
        <span class="chat-bk-go"><?= e($t['msg_view_booking']) ?></span>
    </a>
    <?php endif; ?>
    <?php /* ---- The messages ---- */ ?>
    <ol class="chat-body" id="chat-body">
        <?php if (!$msgs): ?><li class="ch-empty"><?= e($t[$side === 'user' ? 'msg_start' : 'msg_start_staff']) ?></li><?php endif; ?>
        <?php view('partials/chat-bubbles', ['t' => $t, 'msgs' => $msgs, 'side' => $side]); ?>
    </ol>
    <?php /* ---- Send box ---- */ ?>
    <form class="chat-send" method="post" action="<?= e($action) ?>" id="end" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="send">
        <input type="hidden" name="c" value="<?= (int)$conv['id'] ?>">
        <textarea name="body" rows="1" maxlength="<?= MessageModel::MAX_LEN ?>" required placeholder="<?= e($t['msg_type']) ?>" aria-label="<?= e($t['msg_type']) ?>"<?= empty($errors['body']) ? '' : ' class="invalid"' ?>><?= e($draft) ?></textarea>
        <button class="btn chat-go" type="submit" aria-label="<?= e($t['msg_send']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12 20 4l-4 16-4-7z"/><path d="m12 13 8-9"/></svg><span><?= e($t['msg_send']) ?></span></button>
        <?php if (!empty($errors['body'])): ?><p class="error-text" role="alert"><?= e($errors['body']) ?></p><?php endif; ?>
    </form>
</div>
