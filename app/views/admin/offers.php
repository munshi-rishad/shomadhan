<?php
// app/views/admin/offers.php - ?page=offers
// Latest offers: every user sees them under the bell icon. Add, edit (title, text, picture), show / hide, delete.
// Shown inside admin/layout.php (AdminController). Gets: $offers, $edit (row or null)
// + the layout's $t, $base, $canEdit, $err, $val, $hidden. View-only staff see just the list.
?>
<h1 class="adm-h1"><?= e($t['adm_menu_offers']) ?></h1>

<?php /* ---- Add / edit form (only for those who may change this page) ---- */ ?>
<?php if ($canEdit): ?>
<?php $o = $edit ?: []; $isEdit = (bool)$edit; ?>
<form class="card form-grid" method="post" enctype="multipart/form-data" action="<?= e($base) ?>?page=offers<?= $isEdit ? '&edit=' . (int)$o['id'] : '' ?>"<?= $isEdit ? ' data-confirm-changes data-confirm-title="' . e($t['adm_offer_edit']) . '"' : '' ?>>
    <h2><?= e($isEdit ? $t['adm_offer_edit'] : $t['adm_offer_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $isEdit ? 'save' : 'add' ?>">
    <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
    <label class="f f-half"><span><?= e($t['adm_offer_title']) ?> *</span><input type="text" name="title" maxlength="100" required data-label="<?= e($t['adm_offer_title']) ?>" value="<?= $val('title', $o['title'] ?? '') ?>"><?= $err('title') ?></label>
    <div class="f f-half photo-pick">
        <?php if ($isEdit && $o['image']): ?><img class="thumb-square" src="<?= e($o['image']) ?>" alt=""><?php endif; ?>
        <label class="f"><span><?= e($t['adm_photo_opt']) ?><?= size_tag('offers', $t) ?></span>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp" data-label="<?= e($t['adm_photo']) ?>">
            <small><?= e(img_hint('offers', $t, $t['adm_types']) . ($isEdit && $o['image'] ? ' ' . $t['adm_keep_old'] : '')) ?></small><?= $err('image') ?></label>
        <?php if ($isEdit && $o['image']): ?>
            <label class="chk"><input type="checkbox" name="image_remove" value="1" data-label="<?= e($t['adm_img_remove']) ?>"><span><?= e($t['adm_img_remove']) ?></span></label>
        <?php endif; ?>
    </div>
    <label class="f f-wide"><span><?= e($t['adm_offer_message']) ?> *</span><textarea name="message" maxlength="500" rows="3" required data-label="<?= e($t['adm_offer_message']) ?>"><?= $val('message', $o['message'] ?? '') ?></textarea><?= $err('message') ?></label>
    <div class="f-wide row-btns">
        <button class="btn" type="submit"><?= e($isEdit ? $t['adm_save'] : $t['adm_offer_send']) ?></button>
        <?php if ($isEdit): ?><a class="btn btn-ghost" href="<?= e($base) ?>?page=offers"><?= e($t['adm_cancel']) ?></a><?php endif; ?>
    </div>
</form>
<?php endif; ?>

<?php /* ---- All offers ---- */ ?>
<div class="card">
    <h2><?= e($t['adm_all_offers']) ?></h2>
    <?php if (!$offers): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php else: ?>
    <div class="table-wrap"><table class="tbl tbl-list tbl-media">
        <thead><tr><th><?= e($t['adm_photo']) ?></th><th><?= e($t['adm_offer_title']) ?></th><th><?= e($t['adm_offer_message']) ?></th><th><?= e($t['adm_status']) ?></th><th><?= e($t['adm_col_added']) ?></th><?php if ($canEdit): ?><th><?= e($t['acc_actions']) ?></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($offers as $n): ?>
            <tr<?= $n['is_active'] ? '' : ' class="dim"' ?>>
                <td class="cell-img cell-thumb"><?php if ($n['image']): ?><img loading="lazy" decoding="async" class="thumb-square" src="<?= e($n['image']) ?>" alt=""><?php else: ?><span class="thumb-square thumb-none">—</span><?php endif; ?></td>
                <td class="cell-name" data-label="<?= e($t['adm_offer_title']) ?>"><b><?= e($n['title']) ?></b></td>
                <td data-label="<?= e($t['adm_offer_message']) ?>" class="cell-wrap"><?= e($n['message']) ?></td>
                <td data-label="<?= e($t['adm_status']) ?>"><span class="pill <?= $n['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($n['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></td>
                <td data-label="<?= e($t['adm_col_added']) ?>"><small><?= e(date('d-m-Y h:i A', strtotime($n['created_at']))) ?></small></td>
                <?php if ($canEdit): ?>
                <td class="cell-acts"><div class="acts">
                    <a class="btn btn-small btn-ghost" href="<?= e($base) ?>?page=offers&edit=<?= (int)$n['id'] ?>"><?= e($t['adm_edit']) ?></a>
                    <form method="post" action="<?= e($base) ?>?page=offers">
                        <?= $hidden('toggle', $n['id']) ?>
                        <button class="btn btn-small btn-ghost" type="submit"><?= e($n['is_active'] ? $t['adm_hide'] : $t['adm_show']) ?></button>
                    </form>
                    <form method="post" action="<?= e($base) ?>?page=offers" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <?= $hidden('delete', $n['id']) ?>
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                </div></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
