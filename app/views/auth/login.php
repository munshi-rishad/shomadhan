<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($t['login_page_title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth">

        <!-- LEFT (PC) / TOP (phone): brand + text -->
        <section class="intro">
            <a class="back" href="index.php" aria-label="<?= e($t['login_back']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></a>
            <div class="brand">
                <img class="logo" src="assets/images/logo.png" alt="Shomadhan">
                <p class="tagline"><?= e($t['tagline']) ?></p>
            </div>
            <img class="illus-m" src="assets/images/login-illustration.svg" alt="">
            <p class="intro-sub"><?= e($t['login_sub1']) ?><br><?= e($t['login_sub2']) ?></p>

            <h1 class="pc-h1"><span><?= e($t['login_h1_a']) ?></span><span class="green"><?= e($t['login_h1_b']) ?></span></h1>
            <div class="pc-extra">
                <ul class="features">
                    <li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z"/><path d="m9 12 2.2 2.2L15.5 10"/></svg><span><b><?= e($t['feat1_t']) ?></b><small><?= e($t['feat1_s']) ?></small></span></li>
                    <li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/></svg><span><b><?= e($t['feat2_t']) ?></b><small><?= e($t['feat2_s']) ?></small></span></li>
                    <li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3 5 13.5h6L10 21l8-10.5h-6z" fill="currentColor"/></svg><span><b><?= e($t['feat3_t']) ?></b><small><?= e($t['feat3_s']) ?></small></span></li>
                    <li><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/><path d="M19 20c0 1.5-2 2-5 2"/></svg><span><b><?= e($t['feat4_t']) ?></b><small><?= e($t['feat4_s']) ?></small></span></li>
                </ul>
                <img class="illus-pc" src="assets/images/login-illustration.svg" alt="">
            </div>
        </section>

        <!-- RIGHT (PC) / BOTTOM (phone): the form -->
        <section class="card">
            <div class="card-head">
                <h2><?= e($t['login_title']) ?></h2>
                <p><?= e($t['login_sub1']) ?><br><?= e($t['login_sub2']) ?></p>
            </div>

            <form class="form" method="post" action="login.php" novalidate
                  data-err-mobile-empty="<?= e($t['err_mobile_empty']) ?>" data-err-mobile-length="<?= e($t['err_mobile_length']) ?>"
                  data-err-mobile-format="<?= e($t['err_mobile_format']) ?>" data-err-password-empty="<?= e($t['err_password_empty']) ?>"
                  data-err-password-min="<?= e($t['err_password_min']) ?>" data-err-password-max="<?= e($t['err_password_max']) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

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

                <div class="field">
                    <label for="password"><?= e($t['label_password']) ?></label>
                    <div class="input<?= !empty($errors['password']) ? ' invalid' : '' ?>">
                        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="<?= e($t['ph_password']) ?>">
                        <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>">
                            <svg class="ic eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c5 0 8.5 4 9.5 6a14 14 0 0 1-2.9 3.7M6.6 7.6A14 14 0 0 0 2.5 12c1 2 4.5 6 9.5 6 1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            <svg class="ic eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12C3.5 10 7 6 12 6s8.5 4 9.5 6c-1 2-4.5 6-9.5 6s-8.5-4-9.5-6z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p class="error-text" id="err-password" role="alert"<?= empty($errors['password']) ? ' hidden' : '' ?>><?= e($errors['password'] ?? '') ?></p>
                </div>

                <div class="row">
                    <label class="check"><input type="checkbox" name="remember" value="1" checked><span class="box"></span><span><?= e($t['remember_me']) ?></span></label>
                    <a class="link" href="#"><?= e($t['forgot_password']) ?></a>
                </div>

                <button class="btn-submit" type="submit">
                    <span><?= e($t['btn_login']) ?></span>
                    <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
                </button>

                <p class="or"><span><?= e($t['or_text']) ?></span></p>

                <button class="btn-google" type="button">
                    <svg viewBox="0 0 48 48" width="20" height="20" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                    <span><?= e($t['btn_google']) ?></span>
                </button>

                <p class="signup"><?= e($t['no_account']) ?> <a class="link" href="register.php"><?= e($t['create_account']) ?></a></p>
            </form>
        </section>

    </main>
</div>
<script src="assets/js/validation.js"></script>
</body>
</html>
