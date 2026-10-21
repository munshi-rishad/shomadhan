<?php
// app/views/admin/sub-services.php - ?page=sub-services
// Services inside a category (name, photo, price, rating, popular rank) and which providers give each one.
// Shown inside admin/layout.php (AdminController). Gets: $services (categories), $items (sub-services of the filter),
// $filter (category id, 0 = all), $edit (row or null), $providers, $picked (provider ids of the edited row),
// $pvNames (sub-service id => provider names) + the layout's $t, $base, $old, $err, $val, $hidden.
?>
<h1 class="adm-h1"><?= e($t['adm_menu_subs']) ?></h1>

<?php /* No category yet: point to the services page first */ ?>
<?php if (!$services): ?>
    <p class="alert alert-error"><?= e($t['adm_need_service_first']) ?> <a class="link" href="<?= e($base) ?>?page=services"><?= e($t['adm_menu_services']) ?> →</a></p>
<?php else: ?>
<?php /* ---- Add / edit form. $q keeps the chosen category in every address of this page ---- */ ?>
<?php $s = $edit ?: []; $isEdit = (bool)$edit; $q = '&service=' . (int)$filter; ?>
<form class="card form-grid" method="post" enctype="multipart/form-data" action="<?= e($base) ?>?page=sub-services<?= $q ?><?= $isEdit ? '&edit=' . (int)$s['id'] : '' ?>">
    <h2><?= e($isEdit ? $t['adm_sub_edit'] : $t['adm_sub_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
    <label class="f f-wide f-half"><span><?= e($t['adm_which_service']) ?> *</span>
        <select name="service_id" required id="sub-service">
            <?php $cur = (int)($old['service_id'] ?? ($s['service_id'] ?? $filter)); ?>
            <?php foreach ($services as $sv): ?>
                <option value="<?= (int)$sv['id'] ?>"<?= (int)$sv['id'] === $cur ? ' selected' : '' ?>><?= e($sv['name_bn'] . ' / ' . $sv['name_en']) ?></option>
            <?php endforeach; ?>
        </select><?= $err('service_id') ?></label>
    <label class="f"><span><?= e($t['adm_name_bn']) ?> *</span><input type="text" name="name_bn" maxlength="80" required value="<?= $val('name_bn', $s['name_bn'] ?? '') ?>"><?= $err('name_bn') ?></label>
    <label class="f"><span><?= e($t['adm_name_en']) ?> *</span><input type="text" name="name_en" maxlength="80" required value="<?= $val('name_en', $s['name_en'] ?? '') ?>"><?= $err('name_en') ?></label>
    <label class="f"><span><?= e($t['adm_detail_bn']) ?></span><input type="text" name="detail_bn" maxlength="120" value="<?= $val('detail_bn', $s['detail_bn'] ?? '') ?>"></label>
    <label class="f"><span><?= e($t['adm_detail_en']) ?></span><input type="text" name="detail_en" maxlength="120" value="<?= $val('detail_en', $s['detail_en'] ?? '') ?>"></label>
    <label class="f"><span><?= e($t['adm_price']) ?></span><input type="number" name="price" min="0" value="<?= $val('price', $s['price'] ?? '') ?>"></label>
    <label class="f"><span><?= e($t['adm_rating']) ?></span><input type="number" name="rating" min="0" max="5" step="0.1" value="<?= $val('rating', $s['rating'] ?? '') ?>"></label>
    <label class="f"><span><?= e($t['adm_rating_count']) ?></span><input type="number" name="rating_count" min="0" value="<?= $val('rating_count', $s['rating_count'] ?? '') ?>"></label>
    <label class="f f-wide f-half"><span><?= e($t['adm_photo']) ?><?= size_tag('sub-services', $t) ?></span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
        <small><?= e(img_hint('sub-services', $t, $t['adm_photo_hint']) . ($isEdit ? ' ' . $t['adm_keep_old'] : '')) ?></small><?= $err('image') ?>
        <?php if (!empty($s['image'])): ?><img class="thumb" src="<?= e($s['image']) ?>" alt=""><?php endif; ?></label>
    <?php /* Who gives this service: only providers of the chosen category are shown (script at the bottom) */ ?>
    <fieldset class="f f-wide">
        <span><?= e($t['pv_who_gives']) ?></span>
        <?php $picked = array_map('intval', (array)($old['providers'] ?? $picked)); ?>
        <div class="pv-pick" id="pv-pick">
            <?php foreach ($providers as $p): ?>
                <label class="chk<?= $p['status'] !== 'active' ? ' off' : '' ?>" data-service="<?= (int)$p['service_id'] ?>">
                    <input type="checkbox" name="providers[]" value="<?= (int)$p['id'] ?>"<?= in_array((int)$p['id'], $picked, true) ? ' checked' : '' ?>>
                    <?php view('partials/avatar', ['name' => $p['name'], 'photo' => $p['photo'], 'class' => 'av-sm']); ?>
                    <span><?= e($p['name']) ?><small><?= e($p['mobile'] . ' · ' . $p['area'] . ($p['status'] !== 'active' ? ' · ' . $t['adm_st_inactive'] : '')) ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="hint-sm" id="pv-none" hidden><?= e($t['pv_none_in_service']) ?> <a class="link" href="<?= e($base) ?>?page=providers"><?= e($t['adm_menu_providers']) ?> →</a></p>
    </fieldset>
    <label class="chk f-wide"><input type="checkbox" name="is_active" value="1"<?= ($old ? isset($old['is_active']) : ($s['is_active'] ?? 1)) ? ' checked' : '' ?>> <?= e($t['adm_show_users']) ?></label>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=sub-services<?= $q ?>"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>

<?php /* ---- All sub-services, with a category filter ---- */ ?>
<div class="card">
    <div class="card-top">
        <h2><?= e($t['adm_all_subs']) ?></h2>
        <form method="get" action="<?= e($base) ?>" class="filter">
            <input type="hidden" name="page" value="sub-services">
            <select name="service" onchange="this.form.submit()">
                <option value="0"><?= e($t['adm_all']) ?></option>
                <?php foreach ($services as $sv): ?>
                    <option value="<?= (int)$sv['id'] ?>"<?= (int)$sv['id'] === (int)$filter ? ' selected' : '' ?>><?= e($sv['name_bn']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if (!$items): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr><th><?= e($t['adm_photo']) ?></th><th><?= e($t['adm_name']) ?></th><th><?= e($t['adm_menu_services']) ?></th><th><?= e($t['adm_price']) ?></th><th><?= e($t['adm_rating']) ?></th><th><?= e($t['adm_rank_short']) ?></th><th><?= e($t['adm_status']) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $x): ?>
            <tr>
                <td class="cell-thumb" data-label="<?= e($t['adm_photo']) ?>"><?php if ($x['image']): ?><img loading="lazy" decoding="async" class="thumb-sm" src="<?= e($x['image']) ?>" alt=""><?php else: ?><span class="tile tile-<?= e($x['service_color']) ?>"><img loading="lazy" decoding="async" src="<?= e($x['service_icon']) ?>" alt=""></span><?php endif; ?></td>
                <td class="cell-name"><b><?= e($x['name_bn']) ?></b> <small><?= e($x['detail_bn']) ?></small><br><small><?= e($x['name_en']) ?></small></td>
                <td class="cell-full" data-label="<?= e($t['adm_menu_services']) ?>"><span><?= e($x['service_bn']) ?></span><?php $pn = $pvNames[(int)$x['id']] ?? []; ?><small class="pv-names"><?= e($pn ? $t['adm_menu_providers'] . ': ' . implode(', ', $pn) : $t['pv_none_short']) ?></small></td>
                <td data-label="<?= e($t['adm_price']) ?>">৳ <?= (int)$x['price'] ?></td>
                <td data-label="<?= e($t['adm_rating']) ?>">★ <?= e($x['rating']) ?> (<?= (int)$x['rating_count'] ?>)</td>
                <td data-label="<?= e($t['adm_rank_short']) ?>"><?= $x['popular_rank'] === null ? '—' : (int)$x['popular_rank'] ?></td>
                <td data-label="<?= e($t['adm_status']) ?>"><span class="pill <?= $x['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($x['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=sub-services<?= $q ?>&edit=<?= (int)$x['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="<?= e($base) ?>?page=sub-services<?= $q ?>" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <?= $hidden('delete', $x['id']) ?>
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
<?php /* ---- Provider list follows the chosen category ---- */ ?>
<script>
// sub-service form: show only the providers of the chosen category (others are unticked, the server also keeps only these)
(function () {
    var sel = document.getElementById('sub-service'), box = document.getElementById('pv-pick'), none = document.getElementById('pv-none');
    if (!sel || !box) { return; }
    function show() {
        var n = 0;
        [].forEach.call(box.children, function (l) {
            var on = l.getAttribute('data-service') === sel.value;
            l.hidden = !on;
            if (!on) { l.querySelector('input').checked = false; } else { n++; }
        });
        box.hidden = !n;
        none.hidden = !!n;
    }
    sel.addEventListener('change', show);
    show();
})();
</script>
