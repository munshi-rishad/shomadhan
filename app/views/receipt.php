<?php
// app/views/receipt.php - receipt.php?id=N
// Receipt of a finished booking: who, what, when, the bill and how it was paid. "Print" prints only the receipt
// (or saves it as PDF from the print box). Opened by the user (my bookings) and the office (bookings page).
// Rendered by BookingController::receipt(). Gets: $lang, $t, $b (BookingModel::find row), $site, $back.
$code  = BookingModel::code($b['id']);
$bill  = BookingModel::bill($b);
$nm    = $lang === 'en' ? 'en' : 'bn';
$fromWallet = $b['payment'] === 'wallet' ? min((int)$b['total'], $bill) : 0;      // the rest was cash to the provider
$cash  = $bill - $fromWallet;
$money = function ($n) { return '৳ ' . number_format((int)$n); };
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['rc_title'] . ' #' . $code . ' | Shomadhan', 'css' => 'receipt']); ?>
</head>
<body class="rc-body">
<?php /* buttons (not printed) */ ?>
<div class="rc-bar">
    <a class="page-back" href="<?= e($back) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
    <button class="btn" type="button" onclick="window.print()"><?= e($t['rc_print']) ?></button>
</div>

<main class="rc">
    <?php /* ---- Head: logo + company contact | receipt number + date ---- */ ?>
    <header class="rc-head">
        <div>
            <img class="rc-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
            <p><?= e($site['address_' . $nm]) ?><br><?= e($site['phone']) ?><?= $site['email'] ? ' · ' . e($site['email']) : '' ?></p>
        </div>
        <div class="rc-no">
            <h1><?= e($t['rc_title']) ?></h1>
            <p><b>#<?= e($code) ?></b><br><?= e(date('d M Y, h:i A', strtotime($b['completed_at'] ?? $b['scheduled_at']))) ?></p>
        </div>
    </header>

    <?php /* ---- Customer / work ---- */ ?>
    <section class="rc-parties">
        <div><h2><?= e($t['rc_customer']) ?></h2>
            <p><b><?= e($b['user_name']) ?></b><br><?= e($b['user_mobile']) ?><br><?= e($b['area'] . ', ' . $b['address']) ?></p></div>
        <div><h2><?= e($t['rc_work']) ?></h2>
            <p><b><?= e($b['name_' . $nm] ?: $b['name_bn']) ?></b><br><?= e($t['bk_provider']) ?>: <?= e($b['provider_name'] ?? '—') ?><br><?= e(date('d M Y, h:i A', strtotime($b['scheduled_at']))) ?></p></div>
    </section>

    <?php /* ---- The bill ---- */ ?>
    <table class="rc-tbl">
        <tbody>
            <tr><td><?= e($t['bk_price']) ?></td><td><?= e($money($b['price'])) ?></td></tr>
            <?php if ((int)$b['discount'] > 0): ?><tr><td><?= e($t['bk_discount']) ?><?= $b['coupon_code'] ? ' (' . e($b['coupon_code']) . ')' : '' ?></td><td>− <?= e($money($b['discount'])) ?></td></tr><?php endif; ?>
            <?php if ($b['final_total'] !== null): ?>
                <?php if ((int)$b['discount'] > 0): /* after the discount (without one it is the same as the price above) */ ?><tr><td><?= e($t['rc_booked']) ?></td><td><?= e($money($b['total'])) ?></td></tr><?php endif; ?>
                <tr><td><?= e($t['rc_change']) ?><?= $b['final_note'] ? '<br><small>' . e($b['final_note']) . '</small>' : '' ?></td><td><?= $bill >= (int)$b['total'] ? '+ ' : '− ' ?><?= e($money(abs($bill - (int)$b['total']))) ?></td></tr>
            <?php endif; ?>
            <tr class="rc-total"><td><?= e($t['rc_total']) ?></td><td><?= e($money($bill)) ?></td></tr>
        </tbody>
    </table>
    <p class="rc-paid">
        <?php if ($fromWallet > 0): ?><?= e(sprintf($t['rc_paid_wallet'], number_format($fromWallet))) ?><br><?php endif; ?>
        <?php if ($cash > 0): ?><?= e(sprintf($t['rc_paid_cash'], number_format($cash))) ?><?php endif; ?>
    </p>
    <p class="rc-thanks"><?= e($t['rc_thanks']) ?></p>
</main>
</body>
</html>
