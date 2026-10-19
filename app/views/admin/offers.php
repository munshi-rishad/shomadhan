<?php // app/views/admin/offers.php - send a latest offer: every user sees it under the bell icon ?>
<h1 class="adm-h1"><?= e($t['adm_menu_offers']) ?></h1>
<p class="adm-sub"><?= e($t['adm_offers_sub']) ?></p>

<form class="card form-grid" method="post" enctype="multipart/form-data" action="admin-dashboard.php?page=offers">
    <h2><?= e($t['adm_offer_add']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label class="f f-wide"><span><?= e($t['adm_offer_title']) ?> *</span><input type="text" name="title" maxlength="100" required value="<?= $val('title') ?>"><?= $err('title') ?></label>
    <label class="f f-wide"><span><?= e($t['adm_offer_message']) ?> *</span><textarea name="message" maxlength="500" rows="3" required><?= $val('message') ?></textarea><?= $err('message') ?></label>
    <label class="f f-wide"><span><?= e($t['adm_photo_opt']) ?></span><input type="file" name="image" accept="image/png,image/jpeg,image/webp"><?= $err('image') ?></label>
    <div class="f-wide"><button class="btn" type="submit"><?= e($t['adm_offer_send']) ?></button></div>
</form>

<div class="card">
    <h2><?= e($t['adm_all_offers']) ?></h2>
    <?php if (!$offers): ?><p class="empty"><?= e($t['adm_none']) ?></p><?php endif; ?>
    <?php foreach ($offers as $n): ?>
        <div class="offer-row<?= $n['is_active'] ? '' : ' off' ?>">
            <?php if ($n['image']): ?><img src="<?= e($n['image']) ?>" alt=""><?php endif; ?>
            <div class="offer-text">
                <b><?= e($n['title']) ?></b>
                <p><?= e($n['message']) ?></p>
                <small><?= e(date('d M Y, h:i A', strtotime($n['created_at']))) ?> · <?= e($n['is_active'] ? $t['adm_shown'] : $t['adm_hidden']) ?></small>
            </div>
            <div class="acts">
                <form method="post" action="admin-dashboard.php?page=offers">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <button class="btn btn-small btn-ghost" type="submit"><?= e($n['is_active'] ? $t['adm_hide'] : $t['adm_show']) ?></button>
                </form>
                <form method="post" action="admin-dashboard.php?page=offers" data-confirm="<?= e($t['adm_confirm_delete']) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <button class="btn btn-small btn-danger" type="submit"><?= e($t['adm_delete']) ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
