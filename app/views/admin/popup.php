<?php // app/views/admin/popup.php - picture that pops up once when a user opens the home page ?>
<h1 class="adm-h1"><?= e($t['adm_menu_popup']) ?></h1>
<p class="adm-sub"><?= e($t['adm_popup_sub']) ?></p>

<form class="card form-grid" method="post" enctype="multipart/form-data" action="admin-dashboard.php?page=popup">
    <h2><?= e($t['adm_popup_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label class="f f-wide"><span><?= e($t['adm_popup_image']) ?> *</span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
        <small><?= e($t['adm_popup_hint']) ?></small><?= $err('image') ?></label>
    <label class="f f-wide"><span><?= e($t['adm_title_opt']) ?></span><input type="text" name="title" maxlength="100" value="<?= $val('title') ?>"></label>
    <div class="f-wide"><button class="btn" type="submit"><?= e($t['adm_upload']) ?></button></div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_popups']) ?></h2>
    <?php if (!$popups): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <?php $liveId = null; foreach ($popups as $p) { if ($p['is_active']) { $liveId = $p['id']; break; } } ?>
    <div class="banner-list">
        <?php foreach ($popups as $p): ?>
            <div class="banner-row popup-row<?= $p['is_active'] ? '' : ' off' ?>">
                <img src="<?= e($p['image']) ?>" alt="">
                <div class="offer-text">
                    <b><?= e($p['title'] ?: '-') ?></b>
                    <small><?= e(date('d M Y, h:i A', strtotime($p['created_at']))) ?> · <?= e($p['id'] === $liveId ? $t['adm_popup_live'] : ($p['is_active'] ? $t['adm_shown'] : $t['adm_hidden'])) ?></small>
                </div>
                <div class="acts">
                    <form method="post" action="admin-dashboard.php?page=popup">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-small btn-ghost" type="submit"><?= e($p['is_active'] ? $t['adm_hide'] : $t['adm_show']) ?></button>
                    </form>
                    <form method="post" action="admin-dashboard.php?page=popup" data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
