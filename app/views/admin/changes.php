<?php
// app/views/admin/changes.php - ?page=changes
// What the office changed in MY account (who, when, what). The same list the user sees under the bell.
// Shown inside admin/layout.php (AdminController). Gets: $changes (AccountChangeModel::forAccount rows) + $t.
?>
<h1 class="adm-h1"><?= e($t['chg_title']) ?></h1>
<div class="card">
    <?php if (!$changes): ?><p class="empty"><?= e($t['chg_none']) ?></p>
    <?php else: view('partials/account-changes', ['t' => $t, 'changes' => $changes]); endif; ?>
</div>
