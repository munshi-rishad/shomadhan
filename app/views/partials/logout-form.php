<?php
// app/views/partials/logout-form.php
// Logout button. It is a small POST form with the CSRF token, so another site cannot log people out with a link.
// view('partials/logout-form', ['t' => $t, 'class' => 'btn btn-ghost'])
// Optional 'inner' = ready HTML for the button (icon + text); by default the button shows the "logout" text.
?>
<form method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button class="<?= e($class) ?>" type="submit"><?= $inner ?? e($t['btn_logout']) ?></button>
</form>
