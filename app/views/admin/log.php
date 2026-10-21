<?php
// app/views/admin/log.php - ?page=log (admin only)
// Work log: every change an admin / employee saved on the dashboard, newest first. Filter by person and by page.
// Shown inside admin/layout.php. Gets: $list (rows, p, pages, total), $logStaff, $staffId, $logPage + the layout's $t, $base, $pages.
// page names = the menu texts; action names = log_act_* (unknown action: shown as it is)
$pageName = function ($p) use ($t) {
    $key = ['sub-services' => 'adm_menu_subs', 'profile' => 'pf_title', 'changes' => 'chg_menu'][$p] ?? 'adm_menu_' . $p;
    return $t[$key] ?? $p;
};
$url = function (array $set) use ($base, $staffId, $logPage) {
    $q = array_filter(array_merge(['page' => 'log', 'staff' => $staffId ?: null, 'of' => $logPage ?: null], $set));
    return $base . '?' . http_build_query($q);
};
?>
<h1 class="adm-h1"><?= e($t['adm_menu_log']) ?></h1>

<div class="card">
    <form class="list-search" method="get" action="<?= e($base) ?>">
        <input type="hidden" name="page" value="log">
        <select name="staff" aria-label="<?= e($t['log_who']) ?>" onchange="this.form.submit()">
            <option value=""><?= e($t['log_all_staff']) ?></option>
            <?php foreach ($logStaff as $s): ?>
                <option value="<?= (int)$s['id'] ?>"<?= (int)$s['id'] === $staffId ? ' selected' : '' ?>><?= e($s['name']) ?> (<?= e($t['pf_role_' . $s['role']] ?? $s['role']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <select name="of" aria-label="<?= e($t['log_page']) ?>" onchange="this.form.submit()">
            <option value=""><?= e($t['log_all_pages']) ?></option>
            <?php foreach ($pages as $p): if (in_array($p, ['log', 'reports', 'changes'], true)) { continue; } ?>
                <option value="<?= e($p) ?>"<?= $p === $logPage ? ' selected' : '' ?>><?= e($pageName($p)) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn" type="submit"><?= e($t['adm_search']) ?></button></noscript>
    </form>

    <?php if (!$list['rows']): ?><p class="empty"><?= e($t['log_empty']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr><th><?= e($t['log_who']) ?></th><th><?= e($t['log_when']) ?></th><th><?= e($t['log_page']) ?></th><th><?= e($t['log_did']) ?></th><th><?= e($t['log_item']) ?></th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $r): ?>
            <tr>
                <td class="cell-name"><b><?= e($r['staff_name'] ?? '—') ?></b> <small><?= e($t['pf_role_' . $r['staff_role']] ?? '') ?></small></td>
                <td class="cell-id"><?= e(date('d-m-Y h:i A', strtotime($r['created_at']))) ?></td>
                <td data-label="<?= e($t['log_page']) ?>"><?= e($pageName($r['page'])) ?></td>
                <td data-label="<?= e($t['log_did']) ?>"><?= e($t['log_act_' . $r['action']] ?? $r['action']) ?></td>
                <td data-label="<?= e($t['log_item']) ?>" class="cell-wrap"><?= $r['target_id'] ? '#' . (int)$r['target_id'] . ' ' : '' ?><?= e($r['message']) ?: ($r['target_id'] ? '' : '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
    <?php view('partials/pager', ['t' => $t, 'p' => $list['p'], 'pages' => $list['pages'], 'url' => $url]); ?>
</div>
