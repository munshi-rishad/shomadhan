<?php
// app/views/user/book.php - book.php?id=N
// Book a service: date, time, area, address, note, coupon, pay by cash or wallet.
// Pulled into the user page frame (user/home.php, $mode = 'book') by BookingController.
// Gets: $sub (the sub-service), $errors, $old, $areas, $slots, $minDate, $maxDate, $balance,
//       $coupons (usable for this service) + the frame's $t, $nm. The price summary updates live (home.js).

// $fe('date') -> error line under a field, $bad('date') -> ' invalid' class
$fe = function ($k) use ($errors) {
    return empty($errors[$k]) ? '' : '<p class="error-text" role="alert">' . e($errors[$k]) . '</p>';
};
$bad = function ($k) use ($errors) {
    return empty($errors[$k]) ? '' : ' invalid';
};
$price = (int)$sub['price'];
?>
<section class="sec u-page-sec">
    <a class="page-back" href="user-dashboard.php"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
    <div class="u-head"><h1><?= e($t['bk_title']) ?></h1><p><?= e($t['bk_sub']) ?></p></div>
    <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>

    <form class="bk-form" method="post" action="book.php?id=<?= (int)$sub['id'] ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <?php /* ---- The service being booked ---- */ ?>
        <div class="u-card bk-svc">
            <span class="bk-media tile-<?= e($sub['service_color']) ?>">
                <?php if ($sub['image']): ?><img src="<?= e($sub['image']) ?>" alt=""><?php else: ?><img class="ph" src="<?= e($sub['service_icon']) ?>" alt=""><?php endif; ?>
            </span>
            <div>
                <h2><?= e($nm($sub)) ?></h2>
                <?php if ($nm($sub, 'detail')): ?><p class="muted"><?= e($nm($sub, 'detail')) ?></p><?php endif; ?>
                <p class="bk-price">৳ <?= e(number_format($price)) ?> <small><?= e($t['home_from']) ?></small></p>
            </div>
        </div>

        <?php /* ---- When and where ---- */ ?>
        <div class="u-card bk-fields">
            <label class="uf"><span><?= e($t['bk_date']) ?> *</span>
                <input class="ub<?= $bad('date') ?>" type="date" name="date" required min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($old['date']) ?>"><?= $fe('date') ?></label>
            <label class="uf"><span><?= e($t['bk_time']) ?> *</span>
                <select class="ub<?= $bad('time') ?>" name="time" required>
                    <option value=""><?= e($t['bk_time_pick']) ?></option>
                    <?php foreach ($slots as $s): ?>
                        <option value="<?= e($s) ?>"<?= $old['time'] === $s ? ' selected' : '' ?>><?= e(date('h:i A', strtotime($s))) ?></option>
                    <?php endforeach; ?>
                </select><?= $fe('time') ?></label>
            <label class="uf uf-area"><span><?= e($t['pf_area']) ?> *</span>
                <select class="ub<?= $bad('area') ?>" name="area" required>
                    <?php foreach ($areas as $a): ?>
                        <option<?= $old['area'] === $a ? ' selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select><?= $fe('area') ?></label>
            <label class="uf uf-wide"><span><?= e($t['bk_address']) ?> *</span>
                <input class="ub<?= $bad('address') ?>" type="text" name="address" required minlength="5" maxlength="200" placeholder="<?= e($t['bk_address_ph']) ?>" value="<?= e($old['address']) ?>"><?= $fe('address') ?></label>
            <label class="uf uf-wide"><span><?= e($t['bk_note']) ?></span>
                <textarea class="ub<?= $bad('note') ?>" name="note" rows="2" maxlength="255" placeholder="<?= e($t['bk_note_ph']) ?>"><?= e($old['note']) ?></textarea><?= $fe('note') ?></label>
        </div>

        <?php /* ---- Coupon, payment method, price summary, confirm ---- */ ?>
        <div class="u-card bk-pay">
            <label class="uf"><span><?= e($t['bk_coupon']) ?></span>
                <select class="ub<?= $bad('coupon') ?>" name="coupon" id="bk-coupon">
                    <option value="" data-off="0"><?= e($coupons ? $t['bk_coupon_none'] : $t['bk_coupon_empty']) ?></option>
                    <?php foreach ($coupons as $c): $off = min((int)$c['amount'], $price); /* never more off than the price */ ?>
                        <option value="<?= (int)$c['id'] ?>" data-off="<?= $off ?>"<?= (string)$old['coupon'] === (string)$c['id'] ? ' selected' : '' ?>><?= e($c['code'] . ' — ৳' . $off . ' ' . $t['bk_off']) ?></option>
                    <?php endforeach; ?>
                </select><?= $fe('coupon') ?></label>
            <fieldset class="uf bk-methods"><legend><?= e($t['bk_payment']) ?> *</legend>
                <label class="bk-method"><input type="radio" name="payment" value="cash"<?= $old['payment'] !== 'wallet' ? ' checked' : '' ?>>
                    <span><b><?= e($t['bk_pay_cash']) ?></b><small><?= e($t['bk_pay_cash_sub']) ?></small></span></label>
                <label class="bk-method"><input type="radio" name="payment" value="wallet" data-balance="<?= (int)$balance ?>"<?= $old['payment'] === 'wallet' ? ' checked' : '' ?>>
                    <span><b><?= e($t['bk_pay_wallet']) ?></b><small><?= e(sprintf($t['bk_balance'], number_format($balance))) ?></small></span></label>
                <?= $fe('payment') ?>
                <?php /* home.js shows this when "wallet" is picked and the balance is too low */ ?>
                <p class="error-text" id="bk-low" hidden><?= e($t['bk_err_wallet']) ?> <a href="wallet.php#add"><?= e($t['wl_add']) ?></a></p>
            </fieldset>
            <dl class="bk-sum" id="bk-sum" data-price="<?= $price ?>">
                <div><dt><?= e($t['bk_price']) ?></dt><dd>৳ <?= e(number_format($price)) ?></dd></div>
                <div><dt><?= e($t['bk_discount']) ?></dt><dd id="bk-off">− ৳ 0</dd></div>
                <div class="bk-total"><dt><?= e($t['bk_total']) ?></dt><dd id="bk-total">৳ <?= e(number_format($price)) ?></dd></div>
            </dl>
            <button class="btn bk-go" type="submit"><?= e($t['bk_confirm']) ?></button>
        </div>
    </form>
</section>
