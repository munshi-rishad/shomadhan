<?php // app/views/admin/changes.php - what an admin changed in MY account (who, when, what). Same list as the user's bell. ?>
<h1 class="adm-h1"><?= e($t['chg_title']) ?></h1>
<p class="adm-sub"><?= e($t['chg_sub']) ?></p>
<div class="card">
    <?php if (!$changes): ?><p class="empty"><?= e($t['chg_none']) ?></p>
    <?php else: view('partials/account-changes', ['t' => $t, 'changes' => $changes]); endif; ?>
</div>
