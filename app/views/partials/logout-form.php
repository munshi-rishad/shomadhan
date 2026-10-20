<?php // app/views/partials/logout-form.php - logout button (POST + CSRF). view('partials/logout-form', ['t' => $t, 'class' => 'btn btn-ghost']) ?>
<form method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button class="<?= e($class) ?>" type="submit"><?= $inner ?? e($t['btn_logout']) ?></button>
</form>
