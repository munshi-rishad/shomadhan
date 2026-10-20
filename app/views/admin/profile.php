<?php // app/views/admin/profile.php - my profile inside the admin / employee dashboard (same cards as the user's profile page) ?>
<h1 class="adm-h1"><?= e($t['pf_title']) ?></h1>
<p class="adm-sub"><?= e($t['pf_sub']) ?></p>

<div class="pf-grid">
    <div class="card pf-card">
        <?php view('partials/profile-info', ['t' => $t, 'account' => $account, 'action' => $base . '?page=profile', 'photoError' => $errors['photo'] ?? '']); ?>
    </div>
    <div class="pf-col">
        <?php view('partials/profile-forms', ['t' => $t, 'account' => $account, 'errors' => $errors, 'box' => $box ?? '',
            'old' => $old, 'areas' => [], 'action' => $base . '?page=profile']); ?>
    </div>
</div>
