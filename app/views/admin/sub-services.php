<?php // app/views/admin/sub-services.php - services inside a category (name, photo, price, rating, popular rank) ?>
<h1 class="adm-h1"><?= e($t['adm_menu_subs']) ?></h1>
<p class="adm-sub"><?= e($t['adm_subs_sub']) ?></p>

<?php if (!$services): ?>
    <p class="alert alert-error"><?= e($t['adm_need_service_first']) ?> <a class="link" href="admin-dashboard.php?page=services"><?= e($t['adm_menu_services']) ?> →</a></p>
<?php else: ?>
<?php $s = $edit ?: []; $isEdit = (bool)$edit; $q = '&service=' . (int)$filter; ?>
<form class="card form-grid" method="post" enctype="multipart/form-data" action="admin-dashboard.php?page=sub-services<?= $q ?><?= $isEdit ? '&edit=' . (int)$s['id'] : '' ?>">
    <h2><?= e($isEdit ? $t['adm_sub_edit'] : $t['adm_sub_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
    <label class="f f-wide"><span><?= e($t['adm_which_service']) ?> *</span>
        <select name="service_id" required>
            <?php $cur = (int)($old['service_id'] ?? ($s['service_id'] ?? $filter)); foreach ($services as $sv): ?>
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
    <label class="f"><span><?= e($t['adm_popular_rank']) ?></span><input type="number" name="popular_rank" min="1" value="<?= $val('popular_rank', $s['popular_rank'] ?? '') ?>"><small><?= e($t['adm_rank_hint']) ?></small></label>
    <label class="f f-wide"><span><?= e($t['adm_photo']) ?></span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
        <small><?= e($isEdit ? $t['adm_keep_old'] : $t['adm_photo_hint']) ?></small><?= $err('image') ?>
        <?php if (!empty($s['image'])): ?><img class="thumb" src="<?= e($s['image']) ?>" alt=""><?php endif; ?></label>
    <label class="chk f-wide"><input type="checkbox" name="is_active" value="1"<?= ($old ? isset($old['is_active']) : ($s['is_active'] ?? 1)) ? ' checked' : '' ?>> <?= e($t['adm_show_users']) ?></label>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="admin-dashboard.php?page=sub-services<?= $q ?>"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>

<div class="card">
    <div class="card-top">
        <h2><?= e($t['adm_all_subs']) ?></h2>
        <form method="get" action="admin-dashboard.php" class="filter">
            <input type="hidden" name="page" value="sub-services">
            <select name="service" onchange="this.form.submit()">
                <option value="0"><?= e($t['adm_all']) ?></option>
                <?php foreach ($services as $sv): ?><option value="<?= (int)$sv['id'] ?>"<?= (int)$sv['id'] === (int)$filter ? ' selected' : '' ?>><?= e($sv['name_bn']) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php if (!$items): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <div class="table-wrap"><table class="tbl">
        <thead><tr><th><?= e($t['adm_photo']) ?></th><th><?= e($t['adm_name']) ?></th><th><?= e($t['adm_menu_services']) ?></th><th><?= e($t['adm_price']) ?></th><th><?= e($t['adm_rating']) ?></th><th><?= e($t['adm_rank_short']) ?></th><th><?= e($t['adm_status']) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $x): ?>
            <tr>
                <td><?php if ($x['image']): ?><img class="thumb-sm" src="<?= e($x['image']) ?>" alt=""><?php else: ?><span class="tile tile-<?= e($x['service_color']) ?>"><img src="<?= e($x['service_icon']) ?>" alt=""></span><?php endif; ?></td>
                <td><b><?= e($x['name_bn']) ?></b> <small><?= e($x['detail_bn']) ?></small><br><small><?= e($x['name_en']) ?></small></td>
                <td><?= e($x['service_bn']) ?></td>
                <td>৳ <?= (int)$x['price'] ?></td>
                <td>★ <?= e($x['rating']) ?> (<?= (int)$x['rating_count'] ?>)</td>
                <td><?= $x['popular_rank'] === null ? '—' : (int)$x['popular_rank'] ?></td>
                <td><span class="pill <?= $x['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($x['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <td><div class="acts">
                    <a class="btn btn-small btn-ghost" href="admin-dashboard.php?page=sub-services<?= $q ?>&edit=<?= (int)$x['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="admin-dashboard.php?page=sub-services<?= $q ?>" data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
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
