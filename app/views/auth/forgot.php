<?php
// app/views/auth/forgot.php - Forgot password (3 steps: email -> OTP -> new password)
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
$lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
$eyes = '<svg class="ic eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c5 0 8.5 4 9.5 6a14 14 0 0 1-2.9 3.7M6.6 7.6A14 14 0 0 0 2.5 12c1 2 4.5 6 9.5 6 1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
      . '<svg class="ic eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12C3.5 10 7 6 12 6s8.5 4 9.5 6c-1 2-4.5 6-9.5 6s-8.5-4-9.5-6z"/><circle cx="12" cy="12" r="3"/></svg>';
$arrow = '<svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
$sub = $step === 'otp' ? sprintf($t['fp_sub_otp'], $masked) : ($step === 'show' ? $t['fp_sub_new'] : $t['fp_sub_email']);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($t['fp_page_title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth.css')) ?>">
</head>
<body>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth">
        <section class="intro">
            <a class="back" href="login.php" aria-label="<?= e($t['login_back']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></a>
            <div class="brand">
                <img class="logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
                <p class="tagline"><?= e($t['tagline']) ?></p>
            </div>
            <img class="illus-m" src="<?= e(asset('assets/images/login-illustration.svg')) ?>" alt="">
            <p class="intro-sub"><?= e($t['fp_h1_a']) ?><br><?= e($t['fp_h1_b']) ?></p>

            <h1 class="pc-h1"><span><?= e($t['fp_h1_a']) ?></span><span class="green"><?= e($t['fp_h1_b']) ?></span></h1>
            <div class="pc-extra">
                <?php view('partials/features', ['t' => $t]); ?>
                <img class="illus-pc" src="<?= e(asset('assets/images/login-illustration.svg')) ?>" alt="">
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <h2><?= e($t['fp_title']) ?></h2>
                <p><?= e($sub) ?></p>
            </div>

            <ol class="steps" aria-hidden="true">
                <li class="<?= $step === 'email' ? 'on' : 'done' ?>">1</li>
                <li class="<?= $step === 'otp' ? 'on' : ($step === 'show' ? 'done' : '') ?>">2</li>
                <li class="<?= $step === 'show' ? 'on' : '' ?>">3</li>
            </ol>

            <form class="form" method="post" action="forgot-password.php" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

                <?php if ($step === 'email'): ?>
                    <input type="hidden" name="action" value="email">
                    <div class="field">
                        <label for="email"><?= e($t['label_email']) ?></label>
                        <div class="input<?= $inv('email') ?>">
                            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" maxlength="60"
                                   placeholder="<?= e($t['ph_email']) ?>" value="<?= e($old['email']) ?>" required>
                        </div>
                        <?= $err('email') ?>
                    </div>
                    <p class="hint"><?= e($t['fp_no_email_note']) ?></p>
                    <button class="btn-submit" type="submit"><span><?= e($t['btn_next']) ?></span><?= $arrow ?></button>

                <?php elseif ($step === 'otp'): ?>
                    <input type="hidden" name="action" value="otp">
                    <div class="field">
                        <label for="otp"><?= e($t['label_otp']) ?></label>
                        <div class="input<?= $inv('otp') ?>">
                            <?= $lock ?>
                            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" data-digits
                                   class="otp" placeholder="<?= e($t['ph_otp']) ?>" autofocus>
                        </div>
                        <?= $err('otp') ?>
                    </div>
                    <button class="btn-submit" type="submit"><span><?= e($t['btn_verify']) ?></span><?= $arrow ?></button>
                    <div class="row">
                        <button class="link-btn" type="submit" name="action" value="resend" formnovalidate><?= e($t['btn_resend']) ?></button>
                        <a class="link" href="forgot-password.php?restart=1"><?= e($t['fp_change_email']) ?></a>
                    </div>

                <?php else: ?>
                    <div class="field">
                        <label for="shown"><?= e($t['fp_your_password']) ?></label>
                        <div class="input">
                            <?= $lock ?>
                            <input id="shown" type="password" value="<?= e($shownPassword) ?>" readonly>
                            <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?= $eyes ?></button>
                        </div>
                    </div>
                    <p class="hint"><?= e($t['fp_password_once']) ?></p>
                    <a class="btn-submit" href="login.php"><span><?= e($t['btn_go_login']) ?></span><?= $arrow ?></a>
                <?php endif; ?>

                <p class="signup"><a class="link" href="login.php"><?= e($t['fp_back_login']) ?></a></p>
            </form>
        </section>
    </main>
</div>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
</body>
</html>
