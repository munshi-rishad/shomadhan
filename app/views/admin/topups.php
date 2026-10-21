<?php
// app/views/admin/topups.php - ?page=topups
// Add-money requests (admin + employee). The user sent bKash / Nagad / Rocket money to the office number
// and typed the transaction ID. Check it in that account's app, then approve (money goes into the wallet)
// or reject. The user is told.
// Shown inside admin/layout.php (AdminController). Gets: $list (rows, total, p, pages), $tab, $q
// + the layout's $t, $base, $canEdit, $hidden.
$L = $list;

// Link to this list keeping tab / search / page, with some values changed (page 1 and empty values are left out)
$url = function (array $set) use ($base, $tab, $q, $L) {
    $qs = array_merge(['page' => 'topups', 'tab' => $tab, 'q' => $q, 'p' => $L['p']], $set);
    $qs = array_filter($qs, function ($v) { return $v !== '' && $v !== 1; });
    return $base . '?' . http_build_query($qs);
};
$site = HomeContentModel::site();        // the office payment numbers (which number the money was sent to)
?>
<h1 class="adm-h1"><?= e($t['adm_menu_topups']) ?></h1>
<p class="adm-sub"><?= e($t['wl_adm_sub']) ?></p>

<?php /* ---- Status tabs ---- */ ?>
<nav class="u-tabs" aria-label="<?= e($t['adm_menu_topups']) ?>">
    <?php foreach (['pending', 'successful', 'rejected'] as $s): ?>
        <a class="u-tab<?= $tab === $s ? ' on' : '' ?>" href="<?= e($base) ?>?page=topups&amp;tab=<?= $s ?>"<?= $tab === $s ? ' aria-current="page"' : '' ?>><?= e($t['wl_st_' . $s]) ?></a>
    <?php endforeach; ?>
</nav>

<div class="card">
    <?php /* ---- Search ---- */ ?>
    <form class="list-search" method="get" action="<?= e($base) ?>" role="search">
        <input type="hidden" name="page" value="topups"><input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e($t['wl_adm_search']) ?>" aria-label="<?= e($t['wl_adm_search']) ?>">
        <button class="btn" type="submit"><?= e($t['adm_search']) ?></button>
        <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=topups&amp;tab=<?= e($tab) ?>"><?= e($t['adm_clear']) ?></a><?php endif; ?>
    </form>

    <?php /* ---- Requests table (approve / reject buttons only on the pending tab) ---- */ ?>
    <?php if (!$L['rows']): ?><p class="empty"><?= e($q !== '' ? $t['adm_no_match'] : $t['wl_adm_none_' . $tab]) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr>
            <th><?= e($t['bk_adm_user']) ?></th><th><?= e($t['wl_method']) ?></th><th><?= e($t['wl_trx']) ?></th><th><?= e($t['wl_amount']) ?></th>
            <th><?= e($t['wl_adm_sent_at']) ?></th><?php if ($tab !== 'pending'): ?><th><?= e($t['wl_adm_checked']) ?></th><?php endif; ?>
            <?php if ($tab === 'pending' && $canEdit): ?><th><?= e($t['acc_actions']) ?></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($L['rows'] as $w): ?>
            <tr>
                <td class="cell-name" data-label="<?= e($t['bk_adm_user']) ?>"><b><?= e($w['user_name']) ?></b><br><a class="tel" href="tel:+<?= e(phone_intl($w['user_mobile'])) ?>"><?= e($w['user_mobile']) ?></a></td>
                <td data-label="<?= e($t['wl_method']) ?>"><?= e($t['wl_m_' . $w['method']]) ?><br><small><?= e(sprintf($t['wl_adm_to'], $site['pay_' . $w['method']] ?: '—')) ?></small></td>
                <td data-label="<?= e($t['wl_trx']) ?>"><b class="mono"><?= e($w['trx_id']) ?></b></td>
                <td data-label="<?= e($t['wl_amount']) ?>"><b>৳ <?= e(number_format($w['amount'])) ?></b></td>
                <td data-label="<?= e($t['wl_adm_sent_at']) ?>"><small><?= e(date('d-m-Y h:i A', strtotime($w['created_at']))) ?></small></td>
                <?php if ($tab !== 'pending'): ?><td data-label="<?= e($t['wl_adm_checked']) ?>"><?= e($w['checked_name'] ?? '—') ?><br><small><?= $w['checked_at'] ? e(date('d-m-Y h:i A', strtotime($w['checked_at']))) : '' ?></small></td><?php endif; ?>
                <?php if ($tab === 'pending' && $canEdit): ?>
                <td class="cell-acts"><div class="acts">
                    <form method="post" action="<?= e($url([])) ?>" data-confirm-title="<?= e($t['wl_adm_approve']) ?>" data-confirm="<?= e(sprintf($t['wl_adm_approve_q'], $w['trx_id'], number_format($w['amount']), $w['user_name'])) ?>">
                        <?= $hidden('approve', $w['id']) ?><button class="btn btn-small" type="submit"><?= e($t['wl_adm_approve']) ?></button>
                    </form>
                    <form method="post" action="<?= e($url([])) ?>" data-confirm-danger data-confirm-title="<?= e($t['wl_adm_reject']) ?>" data-confirm="<?= e(sprintf($t['wl_adm_reject_q'], $w['trx_id'])) ?>">
                        <?= $hidden('reject', $w['id']) ?><button class="btn btn-small btn-danger" type="submit"><?= e($t['wl_adm_reject']) ?></button>
                    </form>
                </div></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php /* ---- Pages ---- */ ?>
    <?php view('partials/pager', ['t' => $t, 'p' => $L['p'], 'pages' => $L['pages'], 'url' => $url]); ?>
</div>
