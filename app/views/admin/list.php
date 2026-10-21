<?php
// app/views/admin/list.php - ?page=users | employees | admins | providers (| products, no role opens it now)
// One list page for users / employees / admins / service providers / products:
// search box, filters, click a column title to sort (click again = reverse), 25 per page.
// users: edit + delete. employees / admins: add + edit + delete + make admin / employee (changes the role).
// providers: add + edit + delete. Every account change asks "Are you sure?" (our own box, confirm.js) and the admin's own password.
// Employee dashboard: $role is not set, so the form and the action buttons are not shown (view only).
// Shown inside admin/layout.php as $body = 'list' (AdminController). Gets: $list (rows, total, p, pages, sort, dir,
// q, cols, filters), $services, $areas, optional $role + $edit (when this person may change the list)
// + the layout's $t, $lang, $page, $base, $account, $errors, $old, $err, $val, $hidden.
$L = $list;

// Column key => title text key
$colText = [
    'id' => 'adm_col_id', 'name' => 'adm_name', 'mobile' => 'adm_col_mobile', 'email' => 'adm_col_email',
    'area' => 'adm_col_area', 'gender' => 'adm_col_gender', 'status' => 'adm_status', 'age' => 'pv_age',
    'service' => 'pv_service', 'price' => 'adm_price', 'rating' => 'adm_rating', 'providers' => 'adm_menu_providers',
    'popular_rank' => 'adm_rank_short', 'is_active' => 'adm_status', 'created_at' => 'adm_col_added', 'last_login_at' => 'adm_col_last_login', 'created_by' => 'acc_col_created_by',
];
// Link to this list keeping search / filters / sort / page, with some values changed
$url = function (array $set) use ($page, $L, $base) {
    $qs = array_merge(['page' => $page, 'q' => $L['q']] + $L['filters'] + ['sort' => $L['sort'], 'dir' => $L['dir'], 'p' => $L['p']], $set);
    $qs = array_filter($qs, function ($v) { return $v !== ''; });
    if ((int)$qs['p'] <= 1) { unset($qs['p']); }
    return $base . '?' . http_build_query($qs);
};
$isProducts  = $page === 'products';
$isProviders = $page === 'providers';
$nm = $lang === 'en' ? 'en' : 'bn';                        // column suffix for names in the chosen language
// "showing 26-50 of 120"
$from = $L['total'] ? ($L['p'] - 1) * AdminListModel::PER_PAGE + 1 : 0;
$to   = min($L['total'], $L['p'] * AdminListModel::PER_PAGE);
$filtered = $L['q'] !== '' || array_filter($L['filters'], 'strlen');   // search or a filter is on
$canAct = isset($role);                                     // may add / edit / delete here
// Column title, also shown before each value on phone (rows become cards there)
$lbl = function ($c) use ($t, $colText) {
    return ($c === 'id' ? ' class="cell-id"' : '') . ' data-label="' . e($t[$colText[$c]]) . '"';
};
// Service id => name (provider form + service filter)
$sv = [];
foreach ($services as $x) {
    $sv[$x['id']] = $x['name_' . $nm];
}
// Filter dropdowns: name => [all text, [value => text]]
$filterOpts = [];
if (isset($L['filters']['status'])) {
    $filterOpts['status'] = [$t['flt_all_status'], ['active' => $t['adm_st_active'], 'inactive' => $t['adm_st_inactive']]
        + ($canAct ? ['deleted' => $t['acc_st_deleted']] : [])];    // deleted ones: only the admin sees them (to restore)
}
if (isset($L['filters']['service'])) {
    $filterOpts['service'] = [$t['flt_all_service'], $sv];
}
if (isset($L['filters']['gender'])) {
    $filterOpts['gender'] = [$t['flt_all_gender'], ['male' => $t['adm_g_male'], 'female' => $t['adm_g_female'], 'other' => $t['adm_g_other']]];
    $filterOpts['area']   = [$t['flt_all_area'], array_combine($areas, $areas)];
}
if ($isProducts) {
    $filterOpts['shown']   = [$t['flt_all_shown'], ['1' => $t['adm_shown'], '0' => $t['adm_hidden']]];
}
$pw = $isProviders ? '' : ' data-confirm-pw';          // account changes need the admin's own password
?>
<h1 class="adm-h1"><?= e($t['adm_menu_' . $page]) ?></h1>
<p class="adm-sub"><?= e($t['adm_list_sub_' . $page]) ?></p>

<?php /* ---- Add / edit form (opens by itself when editing or after a save error) ---- */ ?>
<?php if ($canAct):
    $a = $edit ?: [];
    $isEdit = (bool)$edit;
    $open = $isEdit || ($errors && ($old['action'] ?? '') === 'save');
    // the admin is editing his own account (he cannot turn himself off)
    $me = $role === 'admin' && $isEdit && (int)$a['id'] === (int)$account['id'];
    $formUrl = $base . '?page=' . $page . ($isEdit ? '&edit=' . (int)$a['id'] : '');
    $needPhoto = false;                                     // photo is optional everywhere
    // Select box: $select(name, label key, [value => text], current value, extra attributes)
    $select = function ($name, $label, array $opts, $cur, $extra = '') use ($t, $err) {
        $h = '<label class="f"><span>' . e($t[$label]) . ' *</span>'
           . '<select name="' . e($name) . '" data-label="' . e($t[$label]) . '"' . $extra . '>';
        foreach ($opts as $v => $text) {
            $h .= '<option value="' . e($v) . '"' . ((string)$v === (string)$cur ? ' selected' : '') . '>' . e($text) . '</option>';
        }
        return $h . '</select>' . $err($name) . '</label>';
    };
    $genders  = ['male' => $t['adm_g_male'], 'female' => $t['adm_g_female'], 'other' => $t['adm_g_other']];
    $statuses = ['active' => $t['adm_st_active'], 'inactive' => $t['adm_st_inactive']];
?>
<?php if ($isEdit || $role !== 'user'): ?>
<details class="card acc-box"<?= $open ? ' open' : '' ?>>
    <summary class="btn<?= $isEdit ? ' btn-hide' : '' ?>">+ <?= e($t['acc_add_' . $role]) ?></summary>
    <form class="form-grid" method="post" action="<?= e($formUrl) ?>" enctype="multipart/form-data" novalidate data-check
          data-unique-url="check-availability.php" data-unique-kind="<?= $role === 'provider' ? 'provider' : 'account' ?>" data-unique-role="<?= e($role) ?>" data-unique-id="<?= (int)($a['id'] ?? 0) ?>"
          <?= $isEdit ? 'data-confirm-changes data-confirm-title="' . e(sprintf($t['acc_confirm_edit'], $a['name'])) . '"'
                      : 'data-confirm-summary data-confirm-title="' . e($t['acc_add_' . $role]) . '" data-confirm="' . e($t[$role === 'provider' ? 'acc_confirm_add_provider' : 'acc_confirm_add']) . '"' ?><?= $pw ?>>
        <h2><?= e($isEdit ? sprintf($t['acc_edit_title'], $a['name']) : $t['acc_add_' . $role]) ?></h2>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($a['id'] ?? 0) ?>">

        <?php /* photo, name, mobile, email, area, gender (everyone) */ ?>
        <div class="f f-half photo-pick">
            <?php view('partials/avatar', ['name' => $a['name'] ?? '?', 'photo' => $a['photo'] ?? null, 'class' => 'pf-avatar']); ?>
            <label class="f"><span><?= e($t['adm_photo']) ?><?= $needPhoto ? ' *' : '' ?><?= size_tag('photos', $t) ?></span>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" data-label="<?= e($t['adm_photo']) ?>"<?= $needPhoto ? ' required' : '' ?>
                       data-rule="photo" data-min-px="<?= PHOTO_MIN ?>" data-max-bytes="<?= PHOTO_MAX_BYTES ?>"
                       data-msg="<?= e($t['ph_err_need']) ?>" data-msg-type="<?= e(sprintf($t['adm_err_type'], 'PNG, JPG, WEBP')) ?>"
                       data-msg-big="<?= e($t['ph_err_too_big']) ?>" data-msg-square="<?= e($t['ph_err_square']) ?>">
                <small><?= e(sprintf($t['ph_hint'], PHOTO_MIN)) ?></small><?= $err('photo') ?></label>
        </div>
        <label class="f"><span><?= e($t['adm_name']) ?> *</span><input type="text" name="name" maxlength="50" required data-rule="name" data-msg="<?= e($t['err_name_format']) ?>" data-label="<?= e($t['adm_name']) ?>" value="<?= $val('name', $a['name'] ?? '') ?>"><small><?= e($t['acc_name_hint']) ?></small><?= $err('name') ?></label>
        <label class="f"><span><?= e($t['adm_col_mobile']) ?> *</span><input type="text" name="mobile" inputmode="numeric" maxlength="11" required data-rule="mobile" data-unique="mobile" data-msg="<?= e($t['err_mobile_format']) ?>" data-label="<?= e($t['adm_col_mobile']) ?>" value="<?= $val('mobile', $a['mobile'] ?? '') ?>" placeholder="01XXXXXXXXX"><?= $err('mobile') ?></label>

        <label class="f"><span><?= e($t['adm_col_email']) ?> <small><?= e($t['optional']) ?></small></span><input type="email" name="email" maxlength="60" data-rule="gmail" data-unique="email" data-msg="<?= e($t['err_email']) ?>" data-label="<?= e($t['adm_col_email']) ?>" value="<?= $val('email', $a['email'] ?? '') ?>" placeholder="name@gmail.com"><?= $err('email') ?></label>
        <?= $select('area', 'adm_col_area', ['' => $t['ph_gender']] + array_combine($areas, $areas), $old['area'] ?? ($a['area'] ?? ''), ' required data-msg="' . e($t['err_area']) . '"') ?>
        <?= $select('gender', 'adm_col_gender', ['' => $t['ph_gender']] + $genders, $old['gender'] ?? ($a['gender'] ?? ''), ' required data-msg="' . e($t['err_gender']) . '"') ?>
        <?php if ($role === 'provider'): /* providers: service, age, status, address, note */ ?>
            <?= $select('service_id', 'pv_service', $sv, $old['service_id'] ?? ($a['service_id'] ?? ''), ' required data-msg="' . e($t['adm_err_required']) . '"') ?>
            <label class="f"><span><?= e($t['pv_age']) ?> *</span><input type="number" name="age" min="18" max="80" required data-rule="range" data-msg="<?= e($t['pv_err_age']) ?>" data-label="<?= e($t['pv_age']) ?>" value="<?= $val('age', $a['age'] ?? '') ?>"><?= $err('age') ?></label>
            <?= $select('status', 'adm_status', $statuses, $old['status'] ?? ($a['status'] ?? 'active'), ' required data-msg="' . e($t['adm_err_required']) . '"') ?>
            <label class="f f-half"><span><?= e($t['pv_address']) ?> *</span><input type="text" name="address" maxlength="200" required data-rule="minlen" data-min="5" data-msg="<?= e($t['pv_err_address']) ?>" data-label="<?= e($t['pv_address']) ?>" value="<?= $val('address', $a['address'] ?? '') ?>" placeholder="<?= e($t['pv_address_ph']) ?>"><?= $err('address') ?></label>
            <label class="f f-half"><span><?= e($t['pv_note']) ?></span><input type="text" name="note" maxlength="255" data-label="<?= e($t['pv_note']) ?>" value="<?= $val('note', $a['note'] ?? '') ?>" placeholder="<?= e($t['pv_note_ph']) ?>"><?= $err('note') ?></label>
        <?php else: /* accounts: status (locked for myself), temporary password only when adding */ ?>
            <?php if ($me): ?>
                <label class="f"><span><?= e($t['adm_status']) ?></span><select disabled><option><?= e($t['adm_st_active']) ?></option></select>
                    <input type="hidden" name="status" value="active"><small><?= e($t['acc_err_self']) ?></small></label>
            <?php else: ?>
                <label class="f"><span><?= e($t['adm_status']) ?> *</span><select name="status" data-label="<?= e($t['adm_status']) ?>">
                    <?php $cur = $old['status'] ?? ($a['status'] ?? 'active'); ?>
                    <?php foreach ($statuses as $v => $text): ?>
                        <option value="<?= $v ?>"<?= $v === $cur ? ' selected' : '' ?>><?= e($text) ?></option>
                    <?php endforeach; ?>
                </select><small><?= e($t['acc_status_hint']) ?></small><?= $err('status') ?></label>
            <?php endif; ?>
            <?php if (!$isEdit): ?>
                <label class="f"><span><?= e($t['acc_temp_password']) ?> *</span><input type="text" name="password" maxlength="128" autocomplete="off" required data-rule="password" data-msg="<?= e($t['err_password_rule']) ?>"><small><?= e($t['acc_temp_password_hint']) ?></small><?= $err('password') ?></label>
            <?php else: ?>
                <p class="f f-wide note-line"><?= e($t['acc_no_password_edit']) ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <div class="f-wide row-btns">
            <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
            <a class="btn btn-ghost" href="<?= e($base) ?>?page=<?= e($page) ?>"><?= e($t['adm_cancel']) ?></a>
        </div>
    </form>
</details>
<?php endif; ?>
<?php endif; ?>

<div class="card">
    <?php /* ---- Search, filters and (phone) sort ---- */ ?>
    <form class="list-search" method="get" action="<?= e($base) ?>" role="search">
        <input type="hidden" name="page" value="<?= e($page) ?>">
        <input type="hidden" name="sort" value="<?= e($L['sort']) ?>">
        <input type="hidden" name="dir" value="<?= e($L['dir']) ?>">
        <input type="search" name="q" value="<?= e($L['q']) ?>" maxlength="60" placeholder="<?= e($t['adm_search_ph_' . ($isProducts ? 'products' : ($isProviders ? 'providers' : 'people'))]) ?>">
        <button class="btn" type="submit"><?= e($t['adm_search']) ?></button>
        <?php if ($filtered): ?><a class="btn btn-ghost" href="<?= e($base . '?page=' . $page) ?>"><?= e($t['adm_clear']) ?></a><?php endif; ?>
        <div class="list-filters">
            <?php foreach ($filterOpts as $name => $f): ?>
                <select name="<?= e($name) ?>" onchange="this.form.submit()" aria-label="<?= e($f[0]) ?>"<?= $L['filters'][$name] !== '' ? ' class="on"' : '' ?>>
                    <option value=""><?= e($f[0]) ?></option>
                    <?php foreach ($f[1] as $v => $text): ?><option value="<?= e($v) ?>"<?= (string)$v === $L['filters'][$name] ? ' selected' : '' ?>><?= e($text) ?></option><?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            <?php /* phones have no column titles to click, so sorting is a dropdown there */ ?>
            <select class="mobile-sort" aria-label="<?= e($t['flt_sort']) ?>"
                    onchange="var v=this.value.split(':');this.form.sort.value=v[0];this.form.dir.value=v[1];this.form.submit()">
                <?php foreach ($L['cols'] as $c): foreach (['asc' => '▲', 'desc' => '▼'] as $d => $arw): ?>
                    <option value="<?= e($c . ':' . $d) ?>"<?= $L['sort'] === $c && $L['dir'] === $d ? ' selected' : '' ?>><?= e($t['flt_sort'] . ': ' . $t[$colText[$c]] . ' ' . $arw) ?></option>
                <?php endforeach; endforeach; ?>
            </select>
        </div>
    </form>
    <div class="list-bar">
        <p class="list-count"><?= e(sprintf($t['adm_list_count'], $from, $to, $L['total'])) ?></p>
    </div>

    <?php /* ---- The table: click a column title to sort ---- */ ?>
    <?php if (!$L['rows']): ?><p class="empty"><?= e($filtered ? $t['adm_no_match'] : $t['adm_none']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list people-list">
        <thead><tr>
            <?php if ($isProducts): ?><th><?= e($t['adm_photo']) ?></th><?php endif; ?>
            <?php foreach ($L['cols'] as $c):
                $on = $L['sort'] === $c;
                $next = $on && $L['dir'] === 'asc' ? 'desc' : 'asc'; ?>
                <th aria-sort="<?= $on ? ($L['dir'] === 'asc' ? 'ascending' : 'descending') : 'none' ?>">
                    <a class="sort<?= $on ? ' on' : '' ?>" href="<?= e($url(['sort' => $c, 'dir' => $next, 'p' => 1])) ?>"><?= e($t[$colText[$c]]) ?><span class="sort-ic" aria-hidden="true"><?= $on ? ($L['dir'] === 'asc' ? '▲' : '▼') : '⇅' ?></span></a>
                </th>
            <?php endforeach; ?>
            <?php if ($canAct): ?><th><?= e($t['acc_actions']) ?></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($L['rows'] as $r): ?>
            <tr>
            <?php if ($isProducts): /* product row: fixed columns */ ?>
                <td class="cell-photo"><?php if ($r['image']): ?><img class="thumb-sm" src="<?= e($r['image']) ?>" alt=""><?php else: ?><span class="tile tile-<?= e($r['service_color']) ?>"><img src="<?= e($r['service_icon']) ?>" alt=""></span><?php endif; ?></td>
                <td<?= $lbl('id') ?>><?= (int)$r['id'] ?></td>
                <td class="cell-name"<?= $lbl('name') ?>><b><?= e($r['name_' . $nm]) ?></b><br><small><?= e($r['name_' . ($nm === 'bn' ? 'en' : 'bn')]) ?></small></td>
                <td<?= $lbl('service') ?>><?= e($r['service_' . $nm]) ?></td>
                <td<?= $lbl('price') ?>>৳ <?= (int)$r['price'] ?></td>
                <td<?= $lbl('rating') ?>>★ <?= e($r['rating']) ?> <small>(<?= (int)$r['rating_count'] ?>)</small></td>
                <td<?= $lbl('popular_rank') ?>><?= $r['popular_rank'] === null ? '—' : (int)$r['popular_rank'] ?></td>
                <td<?= $lbl('providers') ?>><?= (int)$r['providers'] ?: '<span class="pill pill-bad">0</span>' ?></td>
                <td<?= $lbl('is_active') ?>><span class="pill <?= $r['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($r['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <td<?= $lbl('created_at') ?>><small><?= e(date('d-m-Y', strtotime($r['created_at']))) ?></small></td>
            <?php else: /* person / provider row: one cell per sortable column */ ?>
                <?php foreach ($L['cols'] as $c): ?>
                    <?php if ($c === 'status' && $r['deleted_at']): ?>
                        <td<?= $lbl($c) ?>><span class="pill pill-bad"><?= e($t['acc_st_deleted']) ?></span></td>
                    <?php elseif ($c === 'created_by'): ?>
                        <td<?= $lbl($c) ?>><?= $r['created_by'] === null ? '—' : e($r['created_by']) . ($r['created_by_gone'] ? ' <small>(' . e($t['acc_st_deleted']) . ')</small>' : '') ?></td>
                    <?php elseif ($c === 'status'): ?>
                        <td<?= $lbl($c) ?>><span class="pill <?= $r['status'] === 'active' ? 'pill-on' : 'pill-off' ?>"><?= e($t['adm_st_' . $r['status']]) ?></span></td>
                    <?php elseif ($c === 'gender'): ?>
                        <td<?= $lbl($c) ?>><?= e($t['adm_g_' . $r['gender']]) ?></td>
                    <?php elseif ($c === 'name'): ?>
                        <td class="cell-name"<?= $lbl($c) ?>><span class="who">
                            <?php view('partials/avatar', ['name' => $r['name'], 'photo' => $r['photo'], 'class' => 'av-sm']); ?>
                            <span><b><?= e($r['name']) ?></b><?php if ($isProviders): ?><br><small><?= e($r['address']) ?><?= $r['note'] ? ' · ' . e($r['note']) : '' ?></small><?php endif; ?></span>
                        </span></td>
                    <?php elseif ($c === 'created_at'): ?>
                        <td<?= $lbl($c) ?>><small><?= e(date('d-m-Y', strtotime($r['created_at']))) ?></small></td>
                    <?php elseif ($c === 'last_login_at'): ?>
                        <td<?= $lbl($c) ?>><small><?= $r['last_login_at'] ? e(date('d-m-Y h:i A', strtotime($r['last_login_at']))) : e($t['acc_never']) ?></small></td>
                    <?php else: /* email: whole card width on phone (long addresses would split) */ ?>
                        <td<?= $c === 'email' && !empty($r[$c]) ? ' class="cell-full"' : '' ?><?= $lbl($c) ?>><?= ($r[$c] ?? '') === '' || $r[$c] === null ? '—' : str_replace('@', '@<wbr>', e($r[$c])) ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php /* actions: a deleted one can only be restored; others: edit, change role, delete (not myself) */ ?>
                <?php if ($canAct && $r['deleted_at']): ?>
                <td class="cell-acts"><div class="acts">
                    <small class="gone-by"><?= e(sprintf($t['acc_deleted_by'], $r['deleted_by'] ?? '—', date('d-m-Y', strtotime($r['deleted_at'])))) ?></small>
                    <form method="post" action="<?= e($base) ?>?page=<?= e($page) ?>&status=deleted" data-confirm-title="<?= e($t['acc_restore']) ?>" data-confirm="<?= e(sprintf($t['acc_confirm_restore'], $r['name'])) ?>"<?= $pw ?>>
                        <?= $hidden('restore', $r['id']) ?>
                        <button class="btn btn-small btn-move" type="submit"><?= e($t['acc_restore']) ?></button>
                    </form>
                </div></td>
                <?php elseif ($canAct): $isMe = $role === 'admin' && (int)$r['id'] === (int)$account['id']; ?>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=<?= e($page) ?>&edit=<?= (int)$r['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <?php if ($isMe): ?>
                        <span class="pill pill-on"><?= e($t['acc_you']) ?></span>
                    <?php else: ?>
                        <?php if ($role === 'admin' || $role === 'employee'): $newRole = $role === 'admin' ? 'employee' : 'admin'; ?>
                        <form method="post" action="<?= e($base) ?>?page=<?= e($page) ?>" data-confirm-title="<?= e($t['acc_role_change']) ?>"
                              data-confirm="<?= e(sprintf($t['acc_confirm_move'], $r['name'], $t['pf_role_' . $role], $t['pf_role_' . $newRole])) ?>"<?= $pw ?>>
                            <?= $hidden('move', $r['id']) ?>
                            <button class="btn btn-small btn-move" type="submit"><?= e($t['acc_make_' . $newRole]) ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= e($base) ?>?page=<?= e($page) ?>" data-confirm-danger data-confirm-title="<?= e($t['adm_delete']) ?>"
                              data-confirm="<?= e(sprintf($t[$isProviders ? 'pv_confirm_delete' : 'acc_confirm_delete'], $r['name'])) ?>"<?= $pw ?>>
                            <?= $hidden('delete', $r['id']) ?>
                            <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                        </form>
                    <?php endif; ?>
                </div></td>
                <?php endif; ?>
            <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php /* ---- Pages ---- */ ?>
    <?php view('partials/pager', ['t' => $t, 'p' => $L['p'], 'pages' => $L['pages'], 'url' => $url]); ?>
</div>
