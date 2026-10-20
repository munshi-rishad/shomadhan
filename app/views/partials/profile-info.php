<?php
// app/views/partials/profile-info.php - photo, name + all info of the logged-in person (used on every profile page)
// $action = profile form address (shows the 'change photo' button), $photoError = upload error
// Only what the "edit my info" form next to it does NOT show (name, email, area, gender are there; role is under the name)
$rows = ['pf_mobile' => $account['mobile']];
$rows['pf_status'] = $t['adm_st_' . $account['status']];
if (!empty($account['created_at'])) {
    $rows['pf_joined'] = date('d-m-Y', strtotime($account['created_at']));
}
if (!empty($account['last_login_at'])) {
    $rows['pf_last_login'] = date('d-m-Y h:i A', strtotime($account['last_login_at']));
}
?>
<div class="pf">
    <div class="pf-top">
        <?php view('partials/avatar', ['name' => $account['name'], 'photo' => $account['photo'] ?? null, 'class' => 'pf-avatar']); ?>
        <div><h2 class="pf-name"><?= e($account['name']) ?></h2><p class="pf-sub"><?= e($t['pf_role_' . $account['role']]) ?></p></div>
    </div>
    <?php if (!empty($action)): $hasPhoto = !empty($account['photo']); ?>
    <!-- my photo: pick a picture = it is saved at once (square, at least <?= PHOTO_MIN ?> px, max 2 MB) -->
    <form class="pf-photo" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="photo">
        <label class="btn btn-small btn-ghost"><?= e($t[$hasPhoto ? 'ph_change' : 'ph_add']) ?>
            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" hidden onchange="this.form.submit()"></label>
        <?php if ($hasPhoto && $account['role'] === 'user'): ?>
            <button class="btn btn-small btn-danger" type="submit" name="action" value="photo_remove"><?= e($t['ph_remove']) ?></button>
        <?php endif; ?>
    </form>
    <p class="pf-photo-hint"><?= e(sprintf($t['ph_hint'], PHOTO_MIN)) ?></p>
    <?php if (!empty($photoError)): ?><p class="error-text" role="alert"><?= e($photoError) ?></p><?php endif; ?>
    <?php endif; ?>
    <dl class="pf-info">
        <?php foreach ($rows as $k => $v): ?>
            <div><dt><?= e($t[$k]) ?></dt><dd><?= e($v) ?></dd></div>
        <?php endforeach; ?>
    </dl>
</div>
