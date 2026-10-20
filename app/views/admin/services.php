<?php // app/views/admin/services.php - service categories: name + PNG logo ?>
<h1 class="adm-h1"><?= e($t['adm_menu_services']) ?></h1>
<p class="adm-sub"><?= e($t['adm_services_sub']) ?></p>

<?php $s = $edit ?: []; $isEdit = (bool)$edit; ?>
<form class="card form-grid" method="post" enctype="multipart/form-data" action="<?= e($base) ?>?page=services<?= $isEdit ? '&edit=' . (int)$s['id'] : '' ?>">
    <h2><?= e($isEdit ? $t['adm_service_edit'] : $t['adm_service_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>">
    <label class="f"><span><?= e($t['adm_name_bn']) ?> *</span><input type="text" name="name_bn" maxlength="60" required value="<?= $val('name_bn', $s['name_bn'] ?? '') ?>"><?= $err('name_bn') ?></label>
    <label class="f"><span><?= e($t['adm_name_en']) ?> *</span><input type="text" name="name_en" maxlength="60" required value="<?= $val('name_en', $s['name_en'] ?? '') ?>"><?= $err('name_en') ?></label>
    <label class="f"><span><?= e($t['adm_color']) ?></span>
        <select name="color"><?php $cur = $old['color'] ?? ($s['color'] ?? 'mint'); foreach ($colors as $c): ?><option value="<?= e($c) ?>"<?= $c === $cur ? ' selected' : '' ?>><?= e($t['adm_color_' . $c]) ?></option><?php endforeach; ?></select></label>
    <label class="chk f-chk"><input type="checkbox" name="is_active" value="1"<?= ($old ? isset($old['is_active']) : ($s['is_active'] ?? 1)) ? ' checked' : '' ?>> <?= e($t['adm_show_users']) ?></label>
    <label class="f f-half"><span><?= e($t['adm_logo_png']) ?><?= size_tag('services', $t) ?></span>
        <input type="file" name="icon" accept="image/png">
        <small><?= e(img_hint('services', $t, $t['adm_logo_hint']) . ($isEdit ? ' ' . $t['adm_keep_old'] : '')) ?></small><?= $err('icon') ?></label>
    <?php if ($isEdit): ?><div class="f"><span><?= e($t['adm_now']) ?></span><span class="tile tile-<?= e($s['color']) ?>"><img src="<?= e($s['icon']) ?>" alt=""></span></div><?php endif; ?>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($t['adm_save']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=services"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_services']) ?></h2>
    <?php if (!$services): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <div class="table-wrap"><table class="tbl tbl-list">
        <thead><tr><th><?= e($t['adm_logo']) ?></th><th><?= e($t['adm_name']) ?></th><th><?= e($t['adm_menu_subs']) ?></th><th><?= e($t['adm_order']) ?></th><th><?= e($t['adm_status']) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($services as $i => $row): ?>
            <tr>
                <td class="cell-thumb" data-label="<?= e($t['adm_logo']) ?>"><span class="tile tile-<?= e($row['color']) ?>"><img src="<?= e($row['icon']) ?>" alt=""></span></td>
                <td class="cell-name"><b><?= e($row['name_bn']) ?></b><br><small><?= e($row['name_en']) ?></small></td>
                <td data-label="<?= e($t['adm_menu_subs']) ?>"><a class="link" href="<?= e($base) ?>?page=sub-services&service=<?= (int)$row['id'] ?>"><?= (int)$row['sub_count'] ?> →</a></td>
                <td data-label="<?= e($t['adm_order']) ?>"><?php view('partials/order-arrows', ['t' => $t, 'action' => $base . '?page=services', 'id' => $row['id'], 'first' => $i === 0, 'last' => $i === count($services) - 1]); ?></td>
                <td data-label="<?= e($t['adm_status']) ?>"><span class="pill <?= $row['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($row['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=services&edit=<?= (int)$row['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="<?= e($base) ?>?page=services" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete_service']) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
