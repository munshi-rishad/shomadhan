<?php // app/views/admin/popup.php - picture that pops up once when a user opens the home page. Add, edit (title, link, picture), show / hide, delete ?>
<h1 class="adm-h1"><?= e($t['adm_menu_popup']) ?></h1>
<p class="adm-sub"><?= e($t['adm_popup_sub']) ?></p>

<?php $pp = $edit ?: []; $isEdit = (bool)$edit; ?>
<form class="card form-grid" method="post" enctype="multipart/form-data" action="<?= e($base) ?>?page=popup<?= $isEdit ? '&edit=' . (int)$pp['id'] : '' ?>"<?= $isEdit ? ' data-confirm-changes data-confirm-title="' . e($t['adm_popup_edit']) . '"' : '' ?>>
    <h2><?= e($isEdit ? $t['adm_popup_edit'] : $t['adm_popup_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'save' : 'add' ?>">
    <input type="hidden" name="id" value="<?= (int)($pp['id'] ?? 0) ?>">
    <div class="f f-half photo-pick">
        <?php if ($isEdit): ?><img class="thumb-square" src="<?= e($pp['image']) ?>" alt=""><?php endif; ?>
        <label class="f"><span><?= e($t['adm_popup_image']) ?><?= $isEdit ? '' : ' *' ?><?= size_tag('popups', $t) ?></span>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp" data-label="<?= e($t['adm_photo']) ?>"<?= $isEdit ? '' : ' required' ?>>
            <small><?= e(img_hint('popups', $t, $t['adm_types']) . ($isEdit ? ' ' . $t['adm_keep_old'] : '')) ?></small><?= $err('image') ?></label>
    </div>
    <label class="f"><span><?= e($t['adm_title_opt']) ?></span><input type="text" name="title" maxlength="100" data-label="<?= e($t['adm_title_opt']) ?>" value="<?= $val('title', $pp['title'] ?? '') ?>"></label>
    <label class="f"><span><?= e($t['adm_popup_link']) ?></span><input type="url" name="link" maxlength="255" placeholder="https://" data-label="<?= e($t['adm_popup_link']) ?>" value="<?= $val('link', $pp['link'] ?? '') ?>">
        <small><?= e($t['adm_popup_link_hint']) ?></small><?= $err('link') ?></label>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($isEdit ? $t['adm_save'] : $t['adm_upload']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=popup"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_popups']) ?></h2>
    <?php $liveId = null; foreach ($popups as $p) { if ($p['is_active']) { $liveId = $p['id']; break; } } ?>
    <?php if (!$popups): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list tbl-media">
        <thead><tr><th><?= e($t['adm_photo']) ?></th><th><?= e($t['adm_title_opt']) ?></th><th><?= e($t['adm_popup_link']) ?></th><th><?= e($t['adm_status']) ?></th><th><?= e($t['adm_col_added']) ?></th><th><?= e($t['acc_actions']) ?></th></tr></thead>
        <tbody>
        <?php foreach ($popups as $p): ?>
            <tr<?= $p['is_active'] ? '' : ' class="dim"' ?>>
                <td class="cell-img cell-thumb"><img class="thumb-square" src="<?= e($p['image']) ?>" alt=""></td>
                <td class="cell-name" data-label="<?= e($t['adm_title_opt']) ?>"><b><?= e($p['title'] ?: '—') ?></b></td>
                <td data-label="<?= e($t['adm_popup_link']) ?>" class="cell-wrap"><?php if ($p['link']): ?><a class="link" href="<?= e($p['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($p['link']) ?></a><?php else: ?><small><?= e($t['adm_no_link']) ?></small><?php endif; ?></td>
                <td data-label="<?= e($t['adm_status']) ?>"><span class="pill <?= (int)$p['id'] === (int)$liveId ? 'pill-on' : 'pill-off' ?>"><?= e((int)$p['id'] === (int)$liveId ? $t['adm_popup_live'] : ($p['is_active'] ? $t['adm_shown'] : $t['adm_hidden'])) ?></span></td>
                <td data-label="<?= e($t['adm_col_added']) ?>"><small><?= e(date('d-m-Y h:i A', strtotime($p['created_at']))) ?></small></td>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=popup&edit=<?= (int)$p['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="<?= e($base) ?>?page=popup">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-small btn-move" type="submit"><?= e($p['is_active'] ? $t['adm_hide'] : $t['adm_show']) ?></button>
                    </form>
                    <form method="post" action="<?= e($base) ?>?page=popup" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
