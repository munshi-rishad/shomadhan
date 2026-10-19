<?php // app/views/admin/welcome.php - change the coupon picture on the welcome page ?>
<h1 class="adm-h1"><?= e($t['adm_menu_welcome']) ?></h1>
<p class="adm-sub"><?= e($t['adm_welcome_sub']) ?></p>

<div class="card">
    <h2><?= e($t['adm_welcome_now']) ?></h2>
    <img class="welcome-now" src="<?= e(asset($welcome)) ?>" alt="">
    <?php if ($welcome !== $default): ?>
        <form method="post" action="admin-dashboard.php?page=welcome" data-confirm="<?= e($t['adm_welcome_default_q']) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="default">
            <button class="btn btn-small btn-ghost" type="submit"><?= e($t['adm_welcome_default']) ?></button>
        </form>
    <?php endif; ?>
</div>

<form class="card form-grid" method="post" enctype="multipart/form-data" action="admin-dashboard.php?page=welcome">
    <h2><?= e($t['adm_welcome_change']) ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <label class="f f-wide"><span><?= e($t['adm_welcome_image']) ?> *</span>
        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
        <small><?= e($t['adm_welcome_hint']) ?></small><?= $err('image') ?></label>
    <div class="f-wide"><button class="btn" type="submit"><?= e($t['adm_upload']) ?></button></div>
</form>
