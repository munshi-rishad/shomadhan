<?php
// app/views/partials/chat-bubbles.php
// The messages of one chat as bubbles (<li> items). Used inside chat-thread.php, and also sent alone by
// MessageController / the admin messages page as the answer to the live check (chat.js adds new bubbles).
// view('partials/chat-bubbles', ['t' => $t, 'msgs' => MessageModel::messages(...), 'side' => 'user' | 'staff'])
// "mine" (right, green) = what my side wrote. Office messages always show who wrote them (name + admin / employee).
$day = '';
foreach ($msgs as $msg):
    $mine    = $msg['sender'] === $side;
    $msgDay  = date('d M Y', strtotime($msg['created_at']));

    // ---- Day line: shown once before the first message of each day ("Today" for today) ----
    if ($msgDay !== $day): $day = $msgDay; ?>
    <li class="ch-day" data-day="<?= e($msgDay) ?>"><span><?= e($msgDay === date('d M Y') ? $t['msg_today'] : $msgDay) ?></span></li>
    <?php endif; /* ---- then the message bubble ---- */ ?>
    <li class="ch-msg<?= $mine ? ' mine' : '' ?>" data-id="<?= (int)$msg['id'] ?>">
        <?php if ($msg['sender'] === 'staff'): ?><small class="ch-who"><?= e($msg['staff_name']) ?> · <?= e($t['pf_role_' . $msg['staff_role']] ?? '') ?></small><?php endif; ?>
        <p><?= nl2br(e($msg['body'])) ?></p>
        <time><?= e(date('h:i A', strtotime($msg['created_at']))) ?></time>
    </li>
<?php endforeach; ?>
