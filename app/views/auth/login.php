<?php
// app/views/auth/login.php - login.php
// The login page (every role logs in here): mobile + password, "remember me", links to register / forgot password.
// Rendered by AuthController::login(). Gets: $t, $lang, $errors, $notice, $old (mobile, remember).
// validation.js checks the fields in the browser with the texts in data-msg; the server checks them again.
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['login_page_title'], 'css' => 'auth']); ?>
</head>
<body>
<div class="lang-corner"><?php view('partials/lang-switch', ['lang' => $lang]); ?></div>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth">

        <?php /* ---- LEFT (PC) / TOP (phone): brand + text ---- */ ?>
        <?php view('partials/auth-intro', [
            't'     => $t,
            'back'  => 'index.php',
            'illus' => 'login',
            'sub'   => [$t['login_sub1'], $t['login_sub2']],
            'h1'    => [$t['login_h1_a'], $t['login_h1_b']],
        ]); ?>

        <?php /* ---- RIGHT (PC) / BOTTOM (phone): the form ---- */ ?>
        <section class="card">
            <div class="card-head">
                <h2><?= e($t['login_title']) ?></h2>
                <p><?= e($t['login_sub1']) ?><br><?= e($t['login_sub2']) ?></p>
            </div>

            <form class="form" method="post" action="login.php" novalidate data-msg="<?= e(json_encode([
                      'mobile_empty' => $t['err_mobile_empty'], 'mobile_length' => $t['err_mobile_length'], 'mobile_format' => $t['err_mobile_format'],
                      'password_empty' => $t['err_password_empty'], 'password_ascii' => $t['err_password_ascii'],
                      'password_min' => $t['err_password_min'], 'password_max' => $t['err_password_max'],
                  ], JSON_UNESCAPED_UNICODE)) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

                <?php /* ---- Mobile number (+880 only) ---- */ ?>
                <div class="field">
                    <label for="mobile"><?= e($t['label_mobile']) ?></label>
                    <div class="input<?= !empty($errors['mobile']) ? ' invalid' : '' ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                        <span class="cc"><select name="cc" aria-label="+880"><option value="+880">+880</option></select><svg class="ic chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span>
                        <input id="mobile" name="mobile" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="11"
                               placeholder="<?= e($t['ph_mobile']) ?>" value="<?= e($old['mobile']) ?>">
                    </div>
                    <p class="error-text" id="err-mobile" role="alert"<?= empty($errors['mobile']) ? ' hidden' : '' ?>><?= e($errors['mobile'] ?? '') ?></p>
                </div>

                <?php /* ---- Password with show / hide ---- */ ?>
                <div class="field">
                    <label for="password"><?= e($t['label_password']) ?></label>
                    <div class="input<?= !empty($errors['password']) ? ' invalid' : '' ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="<?= e($t['ph_password']) ?>">
                        <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>">
                            <?php view('partials/eye-icons'); ?>
                        </button>
                    </div>
                    <p class="error-text" id="err-password" role="alert"<?= empty($errors['password']) ? ' hidden' : '' ?>><?= e($errors['password'] ?? '') ?></p>
                </div>

                <?php /* ---- Remember me + forgot password ---- */ ?>
                <div class="row">
                    <label class="check"><input type="checkbox" name="remember" value="1"<?= $old['remember'] ? ' checked' : '' ?>><span class="box"></span><span><?= e($t['remember_me']) ?></span></label>
                    <a class="link" href="forgot-password.php"><?= e($t['forgot_password']) ?></a>
                </div>

                <?php /* ---- Login button, Google button, link to register ---- */ ?>
                <button class="btn-submit" type="submit">
                    <span><?= e($t['btn_login']) ?></span>
                    <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                </button>

                <p class="or"><span><?= e($t['or_text']) ?></span></p>

                <?php view('partials/google-btn', ['label' => $t['btn_google']]); ?>

                <p class="signup"><?= e($t['no_account']) ?> <a class="link" href="register.php"><?= e($t['create_account']) ?></a></p>
            </form>
        </section>

    </main>
</div>
<?php view('partials/public-footer', ['t' => $t, 'lang' => $lang]); ?>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
