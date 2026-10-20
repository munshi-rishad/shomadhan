<?php
// app/views/auth/set-password.php - first login of a new admin / employee:
// the admin gave a one-time password, here they must set their own before using the dashboard.
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
$lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
$eyes = '<svg class="ic eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c5 0 8.5 4 9.5 6a14 14 0 0 1-2.9 3.7M6.6 7.6A14 14 0 0 0 2.5 12c1 2 4.5 6 9.5 6 1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
      . '<svg class="ic eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12C3.5 10 7 6 12 6s8.5 4 9.5 6c-1 2-4.5 6-9.5 6s-8.5-4-9.5-6z"/><circle cx="12" cy="12" r="3"/></svg>';
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
        <section class="intro">
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="back" type="submit" aria-label="<?= e($t['fl_logout']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><span class="back-txt"><?= e($t['fl_logout']) ?></span></button>
            </form>
            <div class="brand">
                <img class="logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
                <p class="tagline"><?= e($t['tagline']) ?></p>
            </div>
            <img class="illus-m" src="<?= e(asset('assets/images/login-illustration.svg')) ?>" alt="">
            <p class="intro-sub"><?= e($t['fl_h1_a']) ?><br><?= e($t['fl_h1_b']) ?></p>

            <h1 class="pc-h1"><span><?= e($t['fl_h1_a']) ?></span><span class="green"><?= e($t['fl_h1_b']) ?></span></h1>
            <div class="pc-extra">
                <?php view('partials/features', ['t' => $t]); ?>
                <img class="illus-pc" src="<?= e(asset('assets/images/login-illustration.svg')) ?>" alt="">
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <h2><?= e($t['fl_title']) ?></h2>
                <p><?= e(sprintf($t['fl_sub'], $account['name'])) ?></p>
            </div>

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
                        <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?= $eyes ?></button>
                    </div>
                    <?= $err($name) ?>
                </div>
                <?php endforeach; ?>
                <p class="hint"><?= e($t['cp_rule']) ?></p>
                <button class="btn-submit" type="submit"><span><?= e($t['fp_save_new']) ?></span><?= $arrow ?></button>
            </form>
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
