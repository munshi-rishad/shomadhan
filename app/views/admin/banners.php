<?php // app/views/admin/banners.php - upload / hide / delete the top pictures of the user home page ?>
<h1 class="adm-h1"><?= e($t['adm_menu_banners']) ?></h1>
<p class="adm-sub"><?= e($t['adm_banners_sub']) ?></p>

<form class="card form-grid" method="post" enctype="multipart/form-data" action="<?= e($base) ?>?page=banners">
    <h2><?= e($t['adm_banner_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label class="f f-half"><span><?= e($t['adm_banner_image']) ?> *</span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
        <small><?= e(img_hint('banners', $t, $t['adm_types'])) ?></small><?= $err('image') ?></label>
    <label class="f"><span><?= e($t['adm_title_opt']) ?></span><input type="text" name="title" maxlength="100" value="<?= $val('title') ?>"></label>
    <label class="f"><span><?= e($t['adm_order']) ?></span><input type="number" name="sort_order" value="<?= $val('sort_order', '0') ?>"></label>
    <div class="f-wide"><button class="btn" type="submit"><?= e($t['adm_upload']) ?></button></div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_banners']) ?></h2>
    <?php if (!$banners): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php else: ?>
    <!-- one row per banner: everything on one line, same height. Click the picture to see it big -->
    <div class="table-wrap"><table class="tbl tbl-list tbl-media">
        <thead><tr><th><?= e($t['adm_photo']) ?></th><th><?= e($t['adm_title_opt']) ?></th><th><?= e($t['adm_order']) ?></th><th><?= e($t['adm_show']) ?></th><th><?= e($t['acc_actions']) ?></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): $fid = 'banner-' . (int)$b['id']; ?>
            <tr<?= $b['is_active'] ? '' : ' class="dim"' ?>>
                <td class="cell-img"><img class="thumb-banner" src="<?= e($b['image']) ?>" alt="" data-zoom></td>
                <td data-label="<?= e($t['adm_title_opt']) ?>"><input form="<?= $fid ?>" type="text" name="title" maxlength="100" value="<?= e($b['title']) ?>"></td>
                <td data-label="<?= e($t['adm_order']) ?>"><input form="<?= $fid ?>" class="num" type="number" name="sort_order" value="<?= (int)$b['sort_order'] ?>"></td>
                <td data-label="<?= e($t['adm_show']) ?>"><label class="chk"><input form="<?= $fid ?>" type="checkbox" name="is_active" value="1"<?= $b['is_active'] ? ' checked' : '' ?>> <span class="pill <?= $b['is_active'] ? 'pill-on' : 'pill-off' ?>"><?= e($b['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></span></label></td>
                <td class="cell-acts"><div class="acts">
                    <form id="<?= $fid ?>" method="post" action="<?= e($base) ?>?page=banners">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn btn-small" type="submit"><?= e($t['adm_save']) ?></button>
                    </form>
                    <form method="post" action="<?= e($base) ?>?page=banners" data-confirm-danger data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
