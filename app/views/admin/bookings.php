<?php
// app/views/admin/bookings.php - ?page=bookings
// Users' bookings (admin + employee). Tabs: pending / ongoing / completed / cancelled, search, 25 per page.
//   pending : pick a provider -> "send" (becomes ongoing)
//   ongoing : change provider, "work done", cancel
// Every change is shown to the user under their bell. "Message" opens the chat about that booking.
// Shown inside admin/layout.php (AdminController). Gets: $list (rows, total, p, pages), $tab, $q, $counts (per tab)
// + the layout's $t, $lang, $base, $canEdit, $hidden.
$L  = $list;
$nm = $lang === 'en' ? 'en' : 'bn';      // column suffix for names in the chosen language

// Link to this list keeping tab / search / page, with some values changed (page 1 and empty values are left out)
$url = function (array $set) use ($base, $tab, $q, $L) {
    $qs = array_merge(['page' => 'bookings', 'tab' => $tab, 'q' => $q, 'p' => $L['p']], $set);
    $qs = array_filter($qs, function ($v) { return $v !== '' && $v !== 1; });
    return $base . '?' . http_build_query($qs);
};
$formUrl = $url([]);                     // the row forms post back to the same list
?>
<h1 class="adm-h1"><?= e($t['adm_menu_bookings']) ?></h1>
<p class="adm-sub"><?= e($t['bk_adm_sub']) ?></p>

<?php /* ---- Status tabs with counts ---- */ ?>
<nav class="u-tabs" aria-label="<?= e($t['adm_menu_bookings']) ?>">
    <?php foreach (BookingModel::STATUSES as $s): ?>
        <a class="u-tab<?= $tab === $s ? ' on' : '' ?>" href="<?= e($base) ?>?page=bookings&amp;tab=<?= $s ?>"<?= $tab === $s ? ' aria-current="page"' : '' ?>><?= e($t['bk_st_' . $s]) ?> (<?= (int)$counts[$s] ?>)</a>
    <?php endforeach; ?>
</nav>

<div class="card">
    <?php /* ---- Search ---- */ ?>
    <form class="list-search" method="get" action="<?= e($base) ?>" role="search">
        <input type="hidden" name="page" value="bookings"><input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['bk_adm_search']) ?>" aria-label="<?= e($t['bk_adm_search']) ?>">
        <button class="btn" type="submit"><?= e($t['adm_search']) ?></button>
        <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=bookings&amp;tab=<?= e($tab) ?>"><?= e($t['adm_clear']) ?></a><?php endif; ?>
    </form>

    <?php /* ---- Bookings table ---- */ ?>
    <?php if (!$L['rows']): ?><p class="empty"><?= e($q !== '' ? $t['adm_no_match'] : $t['bk_none_' . $tab]) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list bk-tbl">
        <thead><tr>
            <th><?= e($t['bk_id']) ?></th><th><?= e($t['bk_adm_user']) ?></th><th><?= e($t['bk_adm_service']) ?></th><th><?= e($t['bk_adm_when']) ?></th>
            <th><?= e($t['bk_address']) ?></th><th><?= e($t['bk_total']) ?></th><th><?= e($t['bk_provider']) ?></th><th><?= e($t['acc_actions']) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($L['rows'] as $b): $code = BookingModel::code($b['id']); ?>
            <tr>
                <td class="cell-id" data-label="<?= e($t['bk_id']) ?>"><b>#<?= e($code) ?></b><br><small><?= e(date('d-m-Y h:i A', strtotime($b['created_at']))) ?></small></td>
                <td class="cell-name" data-label="<?= e($t['bk_adm_user']) ?>"><b><?= e($b['user_name']) ?></b><br><a class="tel" href="tel:+<?= e(phone_intl($b['user_mobile'])) ?>"><?= e($b['user_mobile']) ?></a></td>
                <?php $sub = $b['name_' . $nm] ?: $b['name_bn']; /* the category only when it is not the same words */ ?>
                <td data-label="<?= e($t['bk_adm_service']) ?>"><?= e($sub) ?><?php if ($b['service_' . $nm] !== $sub): ?><br><small><?= e($b['service_' . $nm]) ?></small><?php endif; ?></td>
                <td data-label="<?= e($t['bk_adm_when']) ?>"><b><?= e(date('d-m-Y', strtotime($b['scheduled_at']))) ?></b><br><small><?= e(date('h:i A', strtotime($b['scheduled_at']))) ?></small></td>
                <td class="cell-wrap" data-label="<?= e($t['bk_address']) ?>"><?= e($b['area']) ?>, <?= e($b['address']) ?><?php if ($b['note']): ?><br><small class="bk-note">“<?= e($b['note']) ?>”</small><?php endif; ?></td>
                <td data-label="<?= e($t['bk_total']) ?>"><b>৳ <?= e(number_format($b['total'])) ?></b><br><small><?= e($t['bk_pay_' . $b['payment']]) ?><?= $b['coupon_code'] ? ' · ' . e($b['coupon_code']) . ' −৳' . (int)$b['discount'] : '' ?></small></td>
                <?php /* provider: a pick list (send / change), or the provider's name, or "no provider" */ ?>
                <td class="<?= $canEdit && $b['choices'] ? 'cell-full' : '' ?>" data-label="<?= e($t['bk_provider']) ?>">
                    <?php if ($canEdit && $b['choices']): ?>
                        <form class="bk-assign" method="post" action="<?= e($formUrl) ?>"<?= $b['provider_id'] ? ' data-confirm-title="' . e($t['bk_adm_change']) . '" data-confirm-changes' : '' ?>>
                            <?= $hidden('assign', $b['id']) ?>
                            <select name="provider_id" required data-label="<?= e($t['bk_provider']) ?>" aria-label="<?= e($t['bk_provider']) ?>">
                                <?php if (!$b['provider_id']): ?><option value=""><?= e($t['bk_adm_pick']) ?></option><?php endif; ?>
                                <?php foreach ($b['choices'] as $pv): ?>
                                    <option value="<?= (int)$pv['id'] ?>"<?= (int)$pv['id'] === (int)$b['provider_id'] ? ' selected' : '' ?>><?= e($pv['name'] . ' · ' . $pv['area']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-small" type="submit"><?= e($t[$b['provider_id'] ? 'bk_adm_change' : 'bk_adm_send']) ?></button>
                        </form>
                    <?php elseif ($b['provider_name']): ?>
                        <b><?= e($b['provider_name']) ?></b><br><a class="tel" href="tel:+<?= e(phone_intl($b['provider_mobile'])) ?>"><?= e($b['provider_mobile']) ?></a>
                    <?php elseif (in_array($b['status'], ['pending', 'ongoing'], true)): ?>
                        <span class="pill pill-bad"><?= e($t['bk_adm_no_provider']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                    <?php if ($b['status'] === 'completed'): /* extra lines: points earned, why it was cancelled, who handled it */ ?><br><small class="bk-pts">+<?= (int)$b['points'] ?> <?= e($t['wl_points_word']) ?></small><?php endif; ?>
                    <?php if ($b['status'] === 'cancelled'): ?><br><small class="bk-why"><?= e($t[$b['cancelled_by'] === 'staff' ? 'bk_adm_cancel_by_staff' : 'bk_adm_cancel_by_user']) ?><?= $b['cancel_reason'] ? ': ' . e($b['cancel_reason']) : '' ?></small><?php endif; ?>
                    <?php if ($b['handled_name']): ?><br><small><?= e(sprintf($t['bk_adm_by'], $b['handled_name'])) ?></small><?php endif; ?>
                </td>
                <?php /* actions: message the user, work done (ongoing), cancel with a reason (pending / ongoing) */ ?>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=messages&amp;booking=<?= (int)$b['id'] ?>"><?= e($t['msg_about_booking']) ?></a>
                    <?php if ($canEdit && $b['status'] === 'ongoing'): ?>
                        <form method="post" action="<?= e($formUrl) ?>" data-confirm-title="<?= e($t['bk_adm_done']) ?>" data-confirm="<?= e(sprintf($t['bk_adm_done_q'], $code, (int)floor($b['total'] * BookingModel::POINTS_PER_TAKA))) ?>">
                            <?= $hidden('complete', $b['id']) ?><button class="btn btn-small" type="submit"><?= e($t['bk_adm_done']) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($canEdit && in_array($b['status'], ['pending', 'ongoing'], true)): ?>
                        <details class="bk-cancel">
                            <summary class="btn btn-small btn-danger"><?= e($t['bk_cancel']) ?></summary>
                            <form method="post" action="<?= e($formUrl) ?>" data-confirm-danger data-confirm-title="<?= e($t['bk_cancel']) ?>"
                                  data-confirm="<?= e(sprintf($t['bk_cancel_q'], $code) . ($b['payment'] === 'wallet' ? ' ' . $t['bk_cancel_refund'] : '')) ?>">
                                <?= $hidden('cancel', $b['id']) ?>
                                <input type="text" name="reason" maxlength="200" placeholder="<?= e($t['bk_adm_reason']) ?>" aria-label="<?= e($t['bk_adm_reason']) ?>">
                                <button class="btn btn-small btn-danger" type="submit"><?= e($t['bk_adm_cancel_go']) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php /* ---- Pages ---- */ ?>
    <?php view('partials/pager', ['t' => $t, 'p' => $L['p'], 'pages' => $L['pages'], 'url' => $url]); ?>
</div>
