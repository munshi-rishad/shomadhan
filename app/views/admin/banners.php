<?php // app/views/admin/banners.php - upload / hide / delete the top pictures of the user home page ?>
<h1 class="adm-h1"><?= e($t['adm_menu_banners']) ?></h1>
<p class="adm-sub"><?= e($t['adm_banners_sub']) ?></p>

<form class="card form-grid" method="post" enctype="multipart/form-data" action="admin-dashboard.php?page=banners">
    <h2><?= e($t['adm_banner_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label class="f f-wide"><span><?= e($t['adm_banner_image']) ?> *</span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
        <small><?= e($t['adm_banner_hint']) ?></small><?= $err('image') ?></label>
    <label class="f"><span><?= e($t['adm_title_opt']) ?></span><input type="text" name="title" maxlength="100" value="<?= $val('title') ?>"></label>
    <label class="f"><span><?= e($t['adm_order']) ?></span><input type="number" name="sort_order" value="<?= $val('sort_order', '0') ?>"></label>
    <div class="f-wide"><button class="btn" type="submit"><?= e($t['adm_upload']) ?></button></div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_banners']) ?></h2>
    <?php if (!$banners): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <div class="banner-list">
        <?php foreach ($banners as $b): ?>
            <div class="banner-row<?= $b['is_active'] ? '' : ' off' ?>">
                <img src="<?= e($b['image']) ?>" alt="">
                <form class="banner-edit" method="post" action="admin-dashboard.php?page=banners">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <label class="f"><span><?= e($t['adm_title_opt']) ?></span><input type="text" name="title" maxlength="100" value="<?= e($b['title']) ?>"></label>
                    <label class="f f-small"><span><?= e($t['adm_order']) ?></span><input type="number" name="sort_order" value="<?= (int)$b['sort_order'] ?>"></label>
                    <label class="chk"><input type="checkbox" name="is_active" value="1"<?= $b['is_active'] ? ' checked' : '' ?>> <?= e($t['adm_show']) ?></label>
                    <button class="btn btn-small" type="submit"><?= e($t['adm_save']) ?></button>
                </form>
                <form method="post" action="admin-dashboard.php?page=banners" data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
