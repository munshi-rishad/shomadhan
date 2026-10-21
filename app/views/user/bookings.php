<?php
// app/views/user/bookings.php - bookings.php?tab=...
// My bookings. Tabs: pending / ongoing / completed / cancelled.
// Pending: cancel (our own "are you sure" box) or change the date / time. Ongoing: who is coming + call + chat about it.
// Pulled into the user page frame (user/home.php, $mode = 'bookings') by BookingController.
// Completed: receipt, stars + review (once), report a problem (within $complaintDays days) and the office's answer.
// Gets: $bookings, $tab, $counts (per tab), $reschedule (id of the booking whose date form is open), $slots,
//       $minDate, $maxDate, $complaintDays, $errors + the frame's $t, $lang, $nm.

// $ic('<path ...>') -> an icon; $tabIc = icon of each tab
$ic = function ($p) { return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>'; };
$tabIc = [
    'pending'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'ongoing'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    'completed' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'cancelled' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
];
?>
<section class="sec u-page-sec">
    <div class="u-head"><h1><?= e($t['bk_my']) ?></h1><p><?= e($t['bk_my_sub']) ?></p></div>
    <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>

    <?php /* ---- Status tabs with counts ---- */ ?>
    <nav class="u-tabs bk-tabs" aria-label="<?= e($t['bk_my']) ?>">
        <?php foreach (BookingModel::STATUSES as $s): ?>
            <a class="u-tab ut-<?= $s ?><?= $tab === $s ? ' on' : '' ?>" href="bookings.php?tab=<?= $s ?>"<?= $tab === $s ? ' aria-current="page"' : '' ?>>
                <span class="bt-ic"><?= $ic($tabIc[$s]) ?></span><span class="bt-txt"><b><?= e($t['bk_st_' . $s]) ?></b><small><?= e($t['bk_tab_sub_' . $s]) ?></small></span><span class="bt-n"><?= (int)$counts[$s] ?></span></a>
        <?php endforeach; ?>
    </nav>

    <?php /* ---- Nothing in this tab: offer to book ---- */ ?>
    <?php if (!$bookings): ?>
        <div class="u-card u-empty">
            <p><?= e($t['bk_none_' . $tab]) ?></p>
            <a class="btn" href="user-dashboard.php"><?= e($t['bk_book_now']) ?></a>
        </div>
    <?php endif; ?>

    <?php /* ---- One card per booking ---- */ ?>
    <div class="bk-list">
    <?php foreach ($bookings as $b): $code = BookingModel::code($b['id']); ?>
        <article class="u-card bk-card" id="bk-<?= (int)$b['id'] ?>">
            <?php /* picture, details (when, where, number, provider, why cancelled), amount */ ?>
            <div class="bk-top">
                <span class="bk-media tile-<?= e($b['service_color']) ?>">
                    <?php if ($b['image']): ?><img loading="lazy" decoding="async" src="<?= e($b['image']) ?>" alt=""><?php else: ?><img loading="lazy" decoding="async" class="ph" src="<?= e($b['service_icon']) ?>" alt=""><?php endif; ?>
                    <?php $cat = $lang === 'en' ? $b['service_en'] : $b['service_bn']; if ($cat !== $nm($b)): /* category tag only when it is not the same as the name */ ?>
                    <span class="bk-tag"><img loading="lazy" decoding="async" src="<?= e($b['service_icon']) ?>" alt=""><?= e($cat) ?></span><?php endif; ?>
                </span>
                <div class="bk-info">
                    <h2><?= e($nm($b)) ?></h2>
                    <?php if ($nm($b, 'detail')): ?><p class="muted"><?= e($nm($b, 'detail')) ?></p><?php endif; ?>
                    <p class="bk-row"><?= $ic('<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>') ?><?= e(date('d M Y, h:i A', strtotime($b['scheduled_at']))) ?></p>
                    <p class="bk-row"><?= $ic('<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>') ?><?= e($b['area'] . ', ' . $b['address']) ?></p>
                    <p class="bk-row"><?= $ic('<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>') ?><?= e($t['bk_id']) ?>: #<?= e($code) ?></p>
                    <?php if ($b['provider_name'] && $b['status'] !== 'cancelled'): ?>
                        <p class="bk-row bk-pro"><?= $ic('<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>') ?><?= e($t['bk_provider']) ?>: <b><?= e($b['provider_name']) ?></b>
                            <?php if ($b['status'] === 'ongoing'): ?><a class="bk-call" href="tel:+<?= e(phone_intl($b['provider_mobile'])) ?>"><?= e($b['provider_mobile']) ?></a><?php endif; ?></p>
                    <?php endif; ?>
                    <?php /* done: the problem I reported (+ the office's answer), my stars */ ?>
                    <?php if ($b['complaint'] !== null): /* the problem I reported, and the office's answer */ ?>
                        <p class="bk-cmp<?= $b['complaint_status'] === 'open' ? ' open' : '' ?>"><b><?= e($t[$b['complaint_status'] === 'open' ? 'cmp_open_user' : 'bk_cmp_solved']) ?></b> <?= e($b['complaint']) ?>
                            <?php if ($b['complaint_answer']): ?><br><span>→ <?= e($b['complaint_answer']) ?></span><?php endif; ?></p>
                    <?php endif; ?>
                    <?php if ($b['rating'] !== null): /* my stars */ ?>
                        <p class="bk-rated"><span class="bk-stars"><?= str_repeat('★', (int)$b['rating']) ?><s><?= str_repeat('★', 5 - (int)$b['rating']) ?></s></span><?= $b['review'] ? ' “' . e($b['review']) . '”' : '' ?></p>
                    <?php endif; ?>
                    <?php if ($b['status'] === 'cancelled'): ?>
                        <p class="bk-row bk-why"><?= e($t[$b['cancelled_by'] === 'staff' ? 'bk_cancel_by_staff' : 'bk_cancel_by_me']) ?><?= $b['cancel_reason'] ? ': ' . e($b['cancel_reason']) : '' ?></p>
                    <?php endif; ?>
                </div>
                <div class="bk-side">
                    <b class="bk-amt">৳ <?= e(number_format(BookingModel::bill($b))) ?></b>
                    <small><?= e($t['bk_total']) ?></small>
                    <?php if ($b['final_total'] !== null): /* the bill changed after the work: booked amount + why */ ?><small class="bk-final"><?= e(sprintf($t['bk_adm_was'], number_format($b['total']))) ?><?= $b['final_note'] ? ' · ' . e($b['final_note']) : '' ?></small><?php endif; ?>
                    <small class="bk-paid"><?= e($t['bk_pay_' . $b['payment']]) ?><?= $b['discount'] > 0 ? ' · ' . e(sprintf($t['bk_saved'], (int)$b['discount'])) : '' ?></small>
                    <?php if ($b['status'] === 'completed' && $b['points'] > 0): ?><small class="bk-pts">+<?= (int)$b['points'] ?> <?= e($t['wl_points_word']) ?></small><?php endif; ?>
                </div>
            </div>

            <?php if ($b['status'] === 'pending'): /* ---- pending: cancel, change date / time ---- */ ?>
            <div class="bk-acts">
                <form method="post" action="bookings.php?tab=pending" data-confirm-danger data-confirm-title="<?= e($t['bk_cancel']) ?>" data-confirm="<?= e(sprintf($t['bk_cancel_q'], $code) . ($b['payment'] === 'wallet' ? ' ' . $t['bk_cancel_refund'] : '')) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-danger bk-btn" type="submit"><?= $ic('<path d="M18 6 6 18M6 6l12 12"/>') ?><?= e($t['bk_cancel']) ?></button>
                </form>
                <a class="btn bk-btn" href="bookings.php?tab=pending&amp;reschedule=<?= (int)$b['id'] ?>#bk-<?= (int)$b['id'] ?>"><?= $ic('<path d="M4 20h4L19 9l-4-4L4 16z"/>') ?><?= e($t['bk_reschedule']) ?></a>
            </div>
            <?php if ($reschedule === (int)$b['id']): /* the "change date / time" form, opened by the link above */ ?>
            <form class="bk-resched" method="post" action="bookings.php?tab=pending#bk-<?= (int)$b['id'] ?>" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <label class="uf"><span><?= e($t['bk_date']) ?></span>
                    <input class="ub<?= empty($errors['date']) ? '' : ' invalid' ?>" type="date" name="date" required min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($_POST['date'] ?? date('Y-m-d', strtotime($b['scheduled_at']))) ?>">
                    <?php if (!empty($errors['date'])): ?><p class="error-text"><?= e($errors['date']) ?></p><?php endif; ?></label>
                <label class="uf"><span><?= e($t['bk_time']) ?></span>
                    <select class="ub<?= empty($errors['time']) ? '' : ' invalid' ?>" name="time" required>
                        <?php $cur = $_POST['time'] ?? date('H:i', strtotime($b['scheduled_at'])); ?>
                        <?php foreach ($slots as $s): ?>
                            <option value="<?= e($s) ?>"<?= $cur === $s ? ' selected' : '' ?>><?= e(date('h:i A', strtotime($s))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['time'])): ?><p class="error-text"><?= e($errors['time']) ?></p><?php endif; ?></label>
                <div class="bk-resched-btns">
                    <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
                    <a class="btn btn-ghost" href="bookings.php?tab=pending"><?= e($t['adm_cancel']) ?></a>
                </div>
            </form>
            <?php endif; ?>
            <?php elseif ($b['status'] === 'ongoing'): /* ---- ongoing: call the provider, chat about it ---- */ ?>
            <div class="bk-acts">
                <a class="btn btn-ghost bk-btn" href="tel:+<?= e(phone_intl($b['provider_mobile'])) ?>"><?= $ic('<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>') ?><?= e($t['bk_call']) ?></a>
                <a class="btn bk-btn" href="messages.php?booking=<?= (int)$b['id'] ?>"><?= $ic('<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>') ?><?= e($t['msg_about_booking']) ?></a>
            </div>
            <?php elseif ($b['status'] === 'completed'): /* ---- done: receipt, stars, problem ---- */ ?>
            <div class="bk-acts">
                <a class="btn btn-ghost bk-btn" href="receipt.php?id=<?= (int)$b['id'] ?>"><?= $ic('<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>') ?><?= e($t['rc_title']) ?></a>
                <?php if ($b['rating'] === null): ?>
                <details class="bk-more">
                    <summary class="btn bk-btn"><?= $ic('<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>') ?><?= e($t['rv_give']) ?></summary>
                    <form class="bk-rate" method="post" action="bookings.php?tab=completed">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="rate">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <?php /* 5 stars: radio buttons drawn as stars (right to left so CSS can light up the ones before) */ ?>
                        <div class="star-pick" role="radiogroup" aria-label="<?= e($t['rv_give']) ?>">
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <input type="radio" name="stars" value="<?= $n ?>" id="st-<?= (int)$b['id'] ?>-<?= $n ?>" required><label for="st-<?= (int)$b['id'] ?>-<?= $n ?>" title="<?= $n ?>/5">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea class="ub" name="review" rows="2" maxlength="500" placeholder="<?= e($t['rv_ph']) ?>"></textarea>
                        <button class="btn" type="submit"><?= e($t['rv_send']) ?></button>
                    </form>
                </details>
                <?php endif; ?>
                <?php if (BookingModel::canComplain($b, $complaintDays)): ?>
                <details class="bk-more">
                    <summary class="btn btn-ghost bk-btn bk-btn-warn"><?= $ic('<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/>') ?><?= e($t['cmp_give']) ?></summary>
                    <form class="bk-rate" method="post" action="bookings.php?tab=completed">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="complain">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <p class="muted"><?= e(sprintf($t['cmp_hint'], $complaintDays)) ?></p>
                        <textarea class="ub" name="complaint" rows="2" minlength="5" maxlength="500" required placeholder="<?= e($t['cmp_ph']) ?>"></textarea>
                        <button class="btn" type="submit"><?= e($t['cmp_send']) ?></button>
                    </form>
                </details>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
