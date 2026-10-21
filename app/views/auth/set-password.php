<?php
// app/views/auth/set-password.php - set-password.php
// First login of a new admin / employee: the admin gave a one-time password,
// here they must set their own before using the dashboard. The only way out is to log out.
// Rendered by PasswordController. Gets: $t, $lang, $errors, $account (the logged-in person).

// ---- Helpers for this view ----
// $err('new') -> red error line under a field (hidden when empty), $inv('new') -> red border
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
// Icons used more than once
$lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
$arrow = '<svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['fl_title'] . ' | Shomadhan', 'css' => 'auth']); ?>
</head>
<body>
<div class="lang-corner"><?php view('partials/lang-switch', ['lang' => $lang]); ?></div>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth">
        <?php view('partials/auth-intro', [
            't'     => $t,
            'back'  => null,
            'illus' => 'login',
            'sub'   => [$t['fl_h1_a'], $t['fl_h1_b']],
            'h1'    => [$t['fl_h1_a'], $t['fl_h1_b']],
        ]); ?>

        <section class="card">
            <div class="card-head">
                <h2><?= e($t['fl_title']) ?></h2>
                <p><?= e(sprintf($t['fl_sub'], $account['name'])) ?></p>
            </div>

            <?php /* ---- New password twice ---- */ ?>
            <form class="form" method="post" action="set-password.php" novalidate data-check>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php foreach (['new' => ['cp_new', 'ph_password'], 'again' => ['cp_again', 'cp_ph_again']] as $name => $f): ?>
                <div class="field">
                    <label for="<?= $name ?>"><?= e($t[$f[0]]) ?></label>
                    <div class="input<?= $inv($name) ?>">
                        <?= $lock ?>
                        <input id="<?= $name ?>" name="<?= $name ?>" type="password" autocomplete="new-password" maxlength="128" placeholder="<?= e($t[$f[1]]) ?>" required<?= $name === 'new' ? ' autofocus' : '' ?>
                               data-rule="<?= $name === 'new' ? 'password' : 'same:new' ?>" data-msg="<?= e($name === 'new' ? $t['err_password_rule'] : $t['cp_err_again']) ?>">
                        <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?php view('partials/eye-icons'); ?></button>
                    </div>
                    <?= $err($name) ?>
                </div>
                <?php endforeach; ?>
                <p class="hint"><?= e($t['cp_rule']) ?></p>
                <button class="btn-submit" type="submit"><span><?= e($t['fp_save_new']) ?></span><?= $arrow ?></button>
            </form>
            <?php /* ---- Log out instead ---- */ ?>
            <form class="signup" method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="link-btn" type="submit"><?= e($t['fl_logout']) ?></button>
            </form>
        </section>
    </main>
</div>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
