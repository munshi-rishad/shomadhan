<?php
// app/views/admin/reports.php - ?page=reports (admin only)
// How the business did between two dates: bookings made, jobs finished, money, stars, complaints,
// a bar per day (or per month for long ranges), and the best services / areas / providers.
// Shown inside admin/layout.php. Gets: $quick, $from, $to, $sum, $byService, $byArea, $byProvider, $perDay, $perMonth + $t, $base.
$tk    = function ($n) { return '৳ ' . number_format((int)$n); };
$stars = function ($r) { return $r ? number_format((float)$r, 1) . ' ★' : '—'; };
$max   = max(1, max($perDay ?: [0]));
?>
<h1 class="adm-h1"><?= e($t['adm_menu_reports']) ?></h1>

<?php /* ---- Date range: quick chips + own dates ---- */ ?>
<div class="card rp-range">
    <nav class="rp-chips">
        <?php foreach (['today', '7', '30', 'month', 'year', 'all'] as $r): ?>
            <a class="rp-chip<?= $quick === $r ? ' on' : '' ?>" href="<?= e($base) ?>?page=reports&amp;r=<?= e($r) ?>"><?= e($t['rp_r_' . $r]) ?></a>
        <?php endforeach; ?>
    </nav>
    <form class="rp-dates" method="get" action="<?= e($base) ?>">
        <input type="hidden" name="page" value="reports">
        <input type="date" name="from" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e($t['rp_from']) ?>">
        <span>–</span>
        <input type="date" name="to" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e($t['rp_to']) ?>">
        <button class="btn btn-small" type="submit"><?= e($t['rp_show']) ?></button>
    </form>
</div>

<?php /* ---- The numbers ---- */ ?>
<div class="rp-stats">
    <div class="card rp-stat"><small><?= e($t['rp_made']) ?></small><b><?= (int)$sum['made'] ?></b>
        <span><?= e(sprintf($t['rp_made_sub'], (int)$sum['pending'], (int)$sum['ongoing'])) ?></span></div>
    <div class="card rp-stat"><small><?= e($t['rp_done']) ?></small><b><?= (int)$sum['done'] ?></b>
        <span><?= e(sprintf($t['rp_cancelled'], (int)$sum['cancelled'], (int)$sum['cancelled_user'])) ?></span></div>
    <div class="card rp-stat"><small><?= e($t['rp_bill']) ?></small><b><?= e($tk($sum['bill'])) ?></b>
        <span><?= e(sprintf($t['rp_discount'], $tk($sum['discount']))) ?></span></div>
    <div class="card rp-stat rp-main"><small><?= e($t['rp_commission']) ?></small><b><?= e($tk($sum['commission'])) ?></b>
        <span><?= e($t['rp_commission_sub']) ?></span></div>
    <div class="card rp-stat"><small><?= e($t['rp_rating']) ?></small><b><?= e($stars($sum['rating'])) ?></b>
        <span><?= e(sprintf($t['rp_rated'], (int)$sum['rated'])) ?></span></div>
    <div class="card rp-stat"><small><?= e($t['rp_complaints']) ?></small><b><?= (int)$sum['complaints'] ?></b>
        <span><?= e(sprintf($t['rp_open'], (int)$sum['open_complaints'])) ?></span></div>
    <div class="card rp-stat"><small><?= e($t['rp_new_users']) ?></small><b><?= (int)$sum['new_users'] ?></b>
        <span><?= e(sprintf($t['rp_topups'], $tk($sum['topups']))) ?></span></div>
</div>

<?php /* ---- Bookings made per day / month: one bar each, the number shows on hover ---- */ ?>
<div class="card">
    <h2 class="rp-h"><?= e($t[$perMonth ? 'rp_per_month' : 'rp_per_day']) ?></h2>
    <div class="rp-bars" role="img" aria-label="<?= e($t[$perMonth ? 'rp_per_month' : 'rp_per_day']) ?>">
        <?php foreach ($perDay as $d => $n): $label = $perMonth ? date('M Y', strtotime($d . '-01')) : date('d M', strtotime($d)); ?>
            <div class="rp-bar" tabindex="0" data-tip="<?= e($label . ': ' . $n) ?>"><i style="height: <?= round($n / $max * 100, 1) ?>%"></i></div>
        <?php endforeach; ?>
    </div>
    <div class="rp-axis"><span><?= e(date('d M Y', strtotime($from))) ?></span><span><?= e(sprintf($t['rp_max'], $max)) ?></span><span><?= e(date('d M Y', strtotime($to))) ?></span></div>
</div>

<?php /* ---- Best services, areas, providers ---- */ ?>
<div class="rp-tables">
    <div class="card">
        <h2 class="rp-h"><?= e($t['rp_by_service']) ?></h2>
        <?php if (!$byService): ?><p class="empty"><?= e($t['rp_none']) ?></p><?php else: ?>
        <div class="table-wrap"><table class="tbl rp-tbl">
            <thead><tr><th><?= e($t['adm_menu_services']) ?></th><th><?= e($t['po_jobs']) ?></th><th><?= e($t['rp_bill_short']) ?></th><th><?= e($t['po_commission']) ?></th><th>★</th></tr></thead>
            <tbody><?php foreach ($byService as $r): ?>
                <tr><td><?= e($r['name']) ?></td><td><?= (int)$r['jobs'] ?></td><td><?= e($tk($r['bill'])) ?></td><td><?= e($tk($r['commission'])) ?></td><td><?= e($stars($r['rating'])) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2 class="rp-h"><?= e($t['rp_by_area']) ?></h2>
        <?php if (!$byArea): ?><p class="empty"><?= e($t['rp_none']) ?></p><?php else: ?>
        <div class="table-wrap"><table class="tbl rp-tbl">
            <thead><tr><th><?= e($t['adm_col_area']) ?></th><th><?= e($t['rp_made_short']) ?></th><th><?= e($t['po_jobs']) ?></th><th><?= e($t['rp_cancel_short']) ?></th></tr></thead>
            <tbody><?php foreach ($byArea as $r): ?>
                <tr><td><?= e($r['name']) ?></td><td><?= (int)$r['made'] ?></td><td><?= (int)$r['jobs'] ?></td><td><?= (int)$r['cancelled'] ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2 class="rp-h"><?= e($t['rp_by_provider']) ?></h2>
        <?php if (!$byProvider): ?><p class="empty"><?= e($t['rp_none']) ?></p><?php else: ?>
        <div class="table-wrap"><table class="tbl rp-tbl">
            <thead><tr><th><?= e($t['adm_menu_providers']) ?></th><th><?= e($t['po_jobs']) ?></th><th><?= e($t['rp_bill_short']) ?></th><th>★</th><th><?= e($t['rp_cmp_short']) ?></th></tr></thead>
            <tbody><?php foreach ($byProvider as $r): ?>
                <tr><td><a href="<?= e($base) ?>?page=payouts&amp;p=<?= (int)$r['id'] ?>"><?= e($r['name']) ?></a></td><td><?= (int)$r['jobs'] ?></td><td><?= e($tk($r['bill'])) ?></td><td><?= e($stars($r['rating'])) ?></td><td><?= (int)$r['complaints'] ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>
