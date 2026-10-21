<?php
// app/views/user/wallet.php - wallet.php
// My wallet. Main view: balance card, coupons + reward points cards, recent transactions.
// #add / #history / #coupons / #points open as their own screen (CSS :target, no page load). A top-up error keeps #add open.
// Pulled into the user page frame (user/home.php, $mode = 'wallet') by WalletController.
// Gets: $balance, $waiting (top-ups not checked yet), $history (wallet rows, newest first), $coupons (with 'state'),
//       $usable (count), $points, $pointsList (completed bookings that gave points), $methods (payment methods with a
//       number set), $min, $max (top-up limits), $old, $errors + the frame's $t, $lang, $site.

// ---- Small helpers ----
// $ic(paths, class) -> icon, $money(500) -> "৳500.00", $fe / $bad -> field error line / ' invalid' class,
// $back() -> the "back" link of the inner screens
$ic = function ($p, $cls = 'ic') {
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>';
};
$money = function ($n) {
    return '৳' . number_format((int)$n) . '.00';
};
$fe = function ($k) use ($errors) {
    return empty($errors[$k]) ? '' : '<p class="error-text" role="alert">' . e($errors[$k]) . '</p>';
};
$bad = function ($k) use ($errors) {
    return empty($errors[$k]) ? '' : ' invalid';
};
$back = function () use ($t, $ic) {
    return '<a class="page-back" href="wallet.php">' . $ic('<path d="m15 5-7 7 7 7"/>') . e($t['login_back']) . '</a>';
};
$addOpen = !empty($errors);
// Coupon rules line: "min order ... · until ..." (or "no end date")
$cpRules = function ($c) use ($t) {
    return ((int)$c['min_order'] > 0 ? e(sprintf($t['wl_cp_min'], number_format($c['min_order']))) . ' · ' : '')
        . ($c['last_day'] ? e(sprintf($t['wl_cp_until'], date('d M Y', strtotime($c['last_day'])))) : e($t['wl_cp_no_end']));
};

// ---- Transactions ----
// Icon of each kind of wallet row
$txIc = [
    'topup'   => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 15h2"/>',
    'payment' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
    'refund'  => '<path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6"/><path d="M4 4v4.6h4.6"/>',
];
// Booking number of a row ("#SH0012", or '' for a top-up), and its status key:
// a top-up has its own status (pending / successful / rejected), a payment is "paid", a refund "refunded"
$txCode = function ($w) {
    return $w['booking_id'] ? '#' . BookingModel::code($w['booking_id']) : '';
};
$txState = function ($w) {
    if ($w['kind'] === 'topup') {
        return $w['status'];
    }
    return $w['kind'] === 'payment' ? 'paid' : 'refunded';
};
// The name of the booked service in the chosen language
$txService = function ($w) use ($lang) {
    return $lang === 'en' ? ($w['name_en'] ?: $w['name_bn']) : $w['name_bn'];
};
// One transaction line (phone list; used in "recent" and in "all")
$tx = function ($w) use ($t, $ic, $txIc, $txCode, $txState, $txService) {
    $code = $txCode($w);
    $st   = $txState($w);
    if ($w['kind'] === 'topup') {
        $title = sprintf($t['wl_tx_topup'], $t['wl_m_' . $w['method']]);
        $sub   = $t['wl_trx'] . ': ' . $w['trx_id'];
    } elseif ($w['kind'] === 'payment') {
        $title = sprintf($t['wl_tx_payment'], $txService($w));
        $sub   = $t['bk_id'] . ': ' . $code;
    } else {
        $title = $t['wl_tx_refund'];
        $sub   = $t['wl_tx_refund_sub'] . ' ' . $code;
    }
    $amt = (int)$w['amount'];
    ob_start(); ?>
    <li class="wl-tx">
        <span class="wl-tx-ic wt-<?= e($w['kind']) ?>"><?= $ic($txIc[$w['kind']]) ?></span>
        <span class="wl-tx-txt"><b><?= e($title) ?></b><small><?= e($sub) ?></small><small><?= e(date('d M Y, h:i A', strtotime($w['created_at']))) ?></small></span>
        <span class="wl-tx-r">
            <b class="<?= $amt < 0 ? 'neg' : ($w['status'] === 'successful' ? 'pos' : '') ?>"><?= $amt < 0 ? '−' : '+' ?>৳<?= e(number_format(abs($amt))) ?></b>
            <span class="wl-st ws-<?= e($st) ?>"><?= e($t['wl_st_' . $st]) ?></span>
        </span>
    </li>
    <?php return ob_get_clean();
};
// PC: the same transactions as a table (type | details | date | amount | status)
$txRow = function ($w) use ($t, $ic, $txIc, $txCode, $txState, $txService) {
    $code = $txCode($w);
    $st   = $txState($w);
    if ($w['kind'] === 'topup') {
        $what = sprintf($t['wl_d_topup'], $t['wl_m_' . $w['method']]);
        $sub  = $t['wl_trx'] . ': ' . $w['trx_id'];
    } elseif ($w['kind'] === 'payment') {
        $what = $txService($w);
        $sub  = $t['bk_id'] . ': ' . $code;
    } else {
        $what = $t['wl_tx_refund_sub'];
        $sub  = $t['bk_id'] . ': ' . $code;
    }
    $amt = (int)$w['amount'];
    return '<tr><td><span class="wl-type wty-' . e($w['kind']) . '">'
        . '<span class="wl-tx-ic wt-' . e($w['kind']) . '">' . $ic($txIc[$w['kind']]) . '</span>'
        . '<span class="wl-type-pill">' . e($t['wl_type_' . $w['kind']]) . '</span></span></td>'
        . '<td><b>' . e($what) . '</b><small>' . e($sub) . '</small></td>'
        . '<td>' . e(date('d M Y, h:i A', strtotime($w['created_at']))) . '</td>'
        . '<td><b class="' . ($amt < 0 ? 'neg' : ($w['status'] === 'successful' ? 'pos' : '')) . '">' . ($amt < 0 ? '− ' : '+ ') . '৳' . e(number_format(abs($amt))) . '</b></td>'
        . '<td><span class="wl-st ws-' . e($st) . '">' . e($t['wl_st_' . $st]) . '</span></td></tr>';
};
// The whole PC table for a list of rows ('' when there are none)
$txTable = function ($rows) use ($t, $txRow) {
    if (!$rows) {
        return '';
    }
    return '<div class="wl-table-wrap"><table class="wl-table"><thead><tr>'
        . '<th>' . e($t['wl_col_type']) . '</th><th>' . e($t['wl_col_details']) . '</th><th>' . e($t['wl_col_date']) . '</th>'
        . '<th>' . e($t['wl_col_amount']) . '</th><th>' . e($t['adm_status']) . '</th></tr></thead><tbody>'
        . implode('', array_map($txRow, $rows)) . '</tbody></table></div>';
};
// Coupons the user can still use (the first 3 show on the coupon card)
$usableList = array_values(array_filter($coupons, function ($c) { return $c['state'] === 'usable'; }));
?>
<section class="sec u-page-sec wl">

<?php /* ===== MAIN: balance, coupon + points cards, recent transactions ===== */ ?>
<div class="wl-main">
    <div class="u-head"><h1><?= e($t['wl_title']) ?></h1><p><?= e($t['wl_sub']) ?></p></div>

    <div class="wl-top">
        <?php /* ---- Balance card with "add money" and "history" buttons ---- */ ?>
        <div class="wl-balance">
            <div class="wl-bal-head">
                <span class="wl-bal-ic"><?= $ic('<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><path d="M21 9h-5a3 3 0 0 0 0 6h5z"/>') ?></span>
                <div>
                    <p><?= e($t['wl_balance']) ?></p>
                    <b class="wl-amt"><?= e($money($balance)) ?></b>
                    <?php if ($waiting): ?><small class="wl-wait"><?= e(sprintf($t['wl_waiting'], $waiting)) ?></small><?php endif; ?>
                </div>
                <span class="wl-safe"><?= $ic('<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>') ?><?= e($t['wl_safe']) ?></span>
            </div>
            <div class="wl-btns">
                <a class="wl-btn" href="#add"><?= $ic('<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>') ?><?= e($t['wl_add']) ?></a>
                <a class="wl-btn" href="#history"><?= $ic('<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>') ?><?= e($t['wl_history']) ?></a>
            </div>
        </div>

        <?php /* ---- Coupons card (opens #coupons) ---- */ ?>
        <a class="u-card wl-mini wl-cp-card" href="#coupons">
            <span class="wl-mini-ic mi-coupon"><?= $ic('<path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6z"/><path d="M14 5v14" stroke-dasharray="2 2"/>') ?></span>
            <span><b><?= e($t['wl_coupons']) ?></b><small><?= e(sprintf($t['wl_coupons_n'], $usable)) ?></small></span>
            <?= $ic('<path d="m9 5 7 7-7 7"/>', 'ic wl-go') ?>
            <?php if ($usableList): ?>
            <ul class="wl-cp-mini">
                <?php foreach (array_slice($usableList, 0, 3) as $c): ?>
                <li><span class="wl-cp-tag">৳<?= (int)$c['amount'] ?> <?= e($t['wl_off']) ?></span><span><b><?= e($c['code']) ?></b><small><?= $cpRules($c) ?></small></span></li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?><p class="wl-mini-none"><?= e($t['wl_no_coupons']) ?></p><?php endif; ?>
            <span class="wl-more"><?= e($t['wl_all_coupons']) ?> →</span>
        </a>

        <?php /* ---- Reward points card (opens #points) ---- */ ?>
        <a class="u-card wl-mini wl-pt-card" href="#points">
            <span class="wl-mini-ic mi-points"><?= $ic('<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>') ?></span>
            <span><b><?= e($t['wl_points']) ?></b><small><?= e(sprintf($t['wl_points_n'], $points)) ?></small></span>
            <?= $ic('<path d="m9 5 7 7-7 7"/>', 'ic wl-go') ?>
            <span class="wl-pt-stats">
                <span><small><?= e($t['wl_pt_jobs']) ?></small><b><?= count($pointsList) ?></b></span>
                <span><small><?= e($t['wl_pt_rule']) ?></small><b><?= e($t['wl_pt_rule_v']) ?></b></span>
            </span>
            <span class="wl-more"><?= e($t['wl_pt_history']) ?> →</span>
        </a>
    </div>

    <?php /* ---- Last 5 transactions: list on phone, table on PC ---- */ ?>
    <div class="u-card wl-recent">
        <div class="wl-recent-head"><h2><?= e($t['wl_recent']) ?></h2><?php if (count($history) > 5): ?><a href="#history"><?= e($t['home_see_all']) ?> →</a><?php endif; ?></div>
        <?php if (!$history): ?><p class="muted"><?= e($t['wl_none']) ?></p><?php endif; ?>
        <ul class="wl-txs"><?php foreach (array_slice($history, 0, 5) as $w) { echo $tx($w); } ?></ul>
        <?= $txTable(array_slice($history, 0, 5)) ?>
    </div>
</div>

<?php /* ===== ADD MONEY: pick bKash / Nagad / Rocket, amount, transaction ID. The office checks it (admin topups page) ===== */ ?>
<div class="wl-screen<?= $addOpen ? ' open' : '' ?>" id="add">
    <?= $back() ?>
    <div class="u-head"><h1><?= e($t['wl_add']) ?></h1><p><?= e($t['wl_add_sub']) ?></p></div>
    <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
    <?php if (!$methods): ?>
        <p class="alert alert-info"><?= e($t['wl_no_methods']) ?></p>
    <?php else: ?>
    <form class="u-card wl-form" method="post" action="wallet.php" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="topup">
        <ol class="wl-steps">
            <li><?= e($t['wl_step1']) ?></li>
            <li><?= e($t['wl_step2']) ?></li>
            <li><?= e($t['wl_step3']) ?></li>
        </ol>
        <fieldset class="uf wl-methods"><legend><?= e($t['wl_method']) ?> *</legend>
            <?php foreach ($methods as $m): ?>
            <label class="wl-method wm-<?= e($m) ?>"><input type="radio" name="method" value="<?= e($m) ?>"<?= $old['method'] === $m ? ' checked' : '' ?>>
                <span><b><?= e($t['wl_m_' . $m]) ?></b><small><?= e($site['pay_' . $m]) ?></small></span></label>
            <?php endforeach; ?>
            <?= $fe('method') ?>
        </fieldset>
        <label class="uf"><span><?= e($t['wl_amount']) ?> *</span>
            <input class="ub<?= $bad('amount') ?>" type="number" name="amount" inputmode="numeric" required min="<?= (int)$min ?>" max="<?= (int)$max ?>" step="1" placeholder="<?= e(sprintf($t['wl_amount_ph'], $min, number_format($max))) ?>" value="<?= e($old['amount']) ?>"><?= $fe('amount') ?></label>
        <label class="uf"><span><?= e($t['wl_trx']) ?> *</span>
            <input class="ub<?= $bad('trx_id') ?>" type="text" name="trx_id" required minlength="8" maxlength="20" autocomplete="off" placeholder="<?= e($t['wl_trx_ph']) ?>" value="<?= e($old['trx_id']) ?>"><?= $fe('trx_id') ?></label>
        <button class="btn wl-send" type="submit"><?= e($t['wl_send']) ?></button>
    </form>
    <?php endif; ?>
</div>

<?php /* ===== ALL TRANSACTIONS ===== */ ?>
<div class="wl-screen" id="history">
    <?= $back() ?>
    <div class="u-head"><h1><?= e($t['wl_history']) ?></h1><p><?= e(sprintf($t['wl_history_n'], count($history))) ?></p></div>
    <div class="u-card wl-recent">
        <?php if (!$history): ?><p class="muted"><?= e($t['wl_none']) ?></p><?php endif; ?>
        <ul class="wl-txs"><?php foreach ($history as $w) { echo $tx($w); } ?></ul>
        <?= $txTable($history) ?>
    </div>
</div>

<?php /* ===== MY COUPONS (usable, used, expired) ===== */ ?>
<div class="wl-screen" id="coupons">
    <?= $back() ?>
    <div class="u-head"><h1><?= e($t['wl_coupons']) ?></h1><p><?= e($t['wl_coupons_sub']) ?></p></div>
    <?php if (!$coupons): ?><p class="u-card muted"><?= e($t['wl_no_coupons']) ?></p><?php endif; ?>
    <div class="wl-cps">
    <?php foreach ($coupons as $c): ?>
        <div class="u-card wl-cp cs-<?= e($c['state']) ?>">
            <b class="wl-cp-amt">৳<?= (int)$c['amount'] ?></b>
            <div>
                <p class="wl-cp-code"><?= e($c['code']) ?></p>
                <p><?= e($lang === 'en' ? $c['title_en'] : $c['title_bn']) ?></p>
                <small><?= $cpRules($c) ?></small>
            </div>
            <span class="wl-st cps-<?= e($c['state']) ?>"><?= e($t['wl_cp_' . $c['state']]) ?></span>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php /* ===== REWARD POINTS: one line per completed booking ===== */ ?>
<div class="wl-screen" id="points">
    <?= $back() ?>
    <div class="u-head"><h1><?= e($t['wl_points']) ?></h1><p><?= e($t['wl_points_sub']) ?></p></div>
    <div class="u-card wl-recent">
        <?php if (!$pointsList): ?><p class="muted"><?= e($t['wl_no_points']) ?></p><?php endif; ?>
        <ul class="wl-txs">
        <?php foreach ($pointsList as $b): ?>
            <li class="wl-tx">
                <span class="wl-tx-ic tile-<?= e($b['service_color']) ?>"><img src="<?= e($b['service_icon']) ?>" alt=""></span>
                <span class="wl-tx-txt"><b><?= e($lang === 'en' ? ($b['name_en'] ?: $b['name_bn']) : $b['name_bn']) ?></b><small><?= e($t['bk_id']) ?>: #<?= e(BookingModel::code($b['id'])) ?> · ৳<?= e(number_format($b['total'])) ?></small><small><?= e(date('d M Y', strtotime($b['scheduled_at']))) ?></small></span>
                <span class="wl-tx-r"><b class="pos">+<?= (int)$b['points'] ?></b><span class="wl-st ws-points"><?= e($t['wl_points_word']) ?></span></span>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>

</section>
