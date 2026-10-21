<?php
// app/views/partials/account-changes.php
// "What the office changed in your account" list. Used by every role:
// the user's bell panel and the staff dashboard "changes" page (admin/changes.php).
// Needs: $t, $changes (AccountChangeModel::forAccount rows), optional $compact (bell panel: smaller look).
// Each change row has a 'list' of items: [label key, old value, new value].

// ---- Show one old / new value ----
// Codes (status, gender, role) are turned into words in the chosen language; empty = a dash.
$show = function ($key, $value) use ($t) {
    if ($value === null || $value === '') {
        return '—';
    }
    $prefixOf = ['adm_status' => 'adm_st_', 'adm_col_gender' => 'adm_g_', 'pf_role' => 'pf_role_'];
    if (isset($prefixOf[$key]) && isset($t[$prefixOf[$key] . $value])) {
        return $t[$prefixOf[$key] . $value];
    }
    return (string)$value;
};
?>
<ul class="chg-list<?= !empty($compact) ? ' chg-compact' : '' ?>">
    <?php foreach ($changes as $change): ?>
    <li class="chg<?= $change['seen_at'] === null ? ' chg-new' : '' ?>">
        <?php /* ---- Who changed it, and when ---- */ ?>
        <p class="chg-head"><b><?= e($change['by_name']) ?></b> <?= e($t['chg_changed']) ?> <small><?= e(date('d-m-Y h:i A', strtotime($change['created_at']))) ?></small></p>

        <?php /* ---- What was changed: one line per item ---- */ ?>
        <ul class="chg-items">
            <?php foreach ($change['list'] as $item): $key = (string)($item[0] ?? ''); ?>
                <?php if ($key === 'adm_photo'): ?>
                    <li><span><?= e($t['adm_photo']) ?></span> <?= e($t['chg_photo_new']) ?></li>
                <?php elseif ($key === 'bk_event'): /* office changed a booking: [bk_event, new status, booking number] */ ?>
                    <li><span><?= e($t['bk_id']) ?> #<?= e($item[2] ?? '') ?>:</span> <b><?= e($t['bk_st_' . ($item[1] ?? '')] ?? '') ?></b></li>
                <?php elseif ($key === 'wl_event'): /* office checked a wallet top-up: [wl_event, successful | rejected, amount] */ ?>
                    <li><span><?= e($t['wl_topup']) ?> ৳<?= e(number_format((int)($item[2] ?? 0))) ?>:</span> <b><?= e($t['wl_st_' . ($item[1] ?? '')] ?? '') ?></b></li>
                <?php elseif ($key === 'chg_deleted' || $key === 'chg_restored'): ?>
                    <li><?= e($t[$key]) ?></li>
                <?php else: /* a normal field: old value crossed out -> new value */ ?>
                    <li><span><?= e($t[$key] ?? $key) ?>:</span> <s><?= e($show($key, $item[1] ?? '')) ?></s> → <b><?= e($show($key, $item[2] ?? '')) ?></b></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </li>
    <?php endforeach; ?>
</ul>
