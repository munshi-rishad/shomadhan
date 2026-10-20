<?php
// app/views/partials/account-changes.php - "what the admin changed in your account" list (every role: user bell, staff dashboard)
// needs $t, $changes (AccountChangeModel::forAccount rows), optional $compact (bell panel: smaller)
// each change = [label key, old, new]. Codes (status, gender, role) are shown in the chosen language.
$show = function ($key, $v) use ($t) {
    if ($v === null || $v === '') { return '—'; }
    $map = ['adm_status' => 'adm_st_', 'adm_col_gender' => 'adm_g_', 'pf_role' => 'pf_role_'];
    if (isset($map[$key]) && isset($t[$map[$key] . $v])) { return $t[$map[$key] . $v]; }
    return (string)$v;
};
?>
<ul class="chg-list<?= !empty($compact) ? ' chg-compact' : '' ?>">
    <?php foreach ($changes as $c): ?>
    <li class="chg<?= $c['seen_at'] === null ? ' chg-new' : '' ?>">
        <p class="chg-head"><b><?= e($c['by_name']) ?></b> <?= e($t['chg_changed']) ?> <small><?= e(date('d-m-Y h:i A', strtotime($c['created_at']))) ?></small></p>
        <ul class="chg-items">
            <?php foreach ($c['list'] as $x): $k = (string)($x[0] ?? ''); ?>
                <?php if ($k === 'adm_photo'): ?>
                    <li><span><?= e($t['adm_photo']) ?></span> <?= e($t['chg_photo_new']) ?></li>
                <?php elseif ($k === 'chg_deleted' || $k === 'chg_restored'): ?>
                    <li><?= e($t[$k]) ?></li>
                <?php else: ?>
                    <li><span><?= e($t[$k] ?? $k) ?>:</span> <s><?= e($show($k, $x[1] ?? '')) ?></s> → <b><?= e($show($k, $x[2] ?? '')) ?></b></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </li>
    <?php endforeach; ?>
</ul>
