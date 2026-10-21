<?php
// app/views/auth/forgot.php - forgot-password.php
// Forgot password in 3 steps: email -> 6-digit code (OTP) sent by email -> new password.
// Rendered by PasswordController. Gets: $t, $lang, $step ('email' | 'otp' | 'new'), $errors, $notice, $old,
// $masked (the email with letters hidden, shown on the OTP step).
// form-check.js checks the fields in the browser (data-rule / data-msg); the server checks them again.

// ---- Helpers for this view ----
// $err('email') -> red error line under a field (hidden when empty), $inv('email') -> red border
$err = function ($key) use ($errors) {
    $m = $errors[$key] ?? '';
    return '<p class="error-text" id="err-' . e($key) . '" role="alert"' . ($m === '' ? ' hidden' : '') . '>' . e($m) . '</p>';
};
$inv = function ($key) use ($errors) { return !empty($errors[$key]) ? ' invalid' : ''; };
// Icons used more than once
$lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
$arrow = '<svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
// Text under the title: what to do on this step
$sub = $step === 'otp' ? sprintf($t['fp_sub_otp'], $masked) : ($step === 'new' ? $t['fp_sub_new'] : $t['fp_sub_email']);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['fp_page_title'], 'css' => 'auth']); ?>
</head>
<body>
<div class="lang-corner"><?php view('partials/lang-switch', ['lang' => $lang]); ?></div>
<div class="page">
    <div class="bg" aria-hidden="true"></div>

    <main class="auth">
        <?php view('partials/auth-intro', [
            't'     => $t,
            'back'  => 'login.php',
            'illus' => 'login',
            'sub'   => [$t['fp_h1_a'], $t['fp_h1_b']],
            'h1'    => [$t['fp_h1_a'], $t['fp_h1_b']],
        ]); ?>

        <section class="card">
            <div class="card-head">
                <h2><?= e($t['fp_title']) ?></h2>
                <p><?= e($sub) ?></p>
            </div>

            <?php /* ---- Step dots 1 2 3 ---- */ ?>
            <ol class="steps" aria-hidden="true">
                <li class="<?= $step === 'email' ? 'on' : 'done' ?>">1</li>
                <li class="<?= $step === 'otp' ? 'on' : ($step === 'new' ? 'done' : '') ?>">2</li>
                <li class="<?= $step === 'new' ? 'on' : '' ?>">3</li>
            </ol>

            <form class="form" method="post" action="forgot-password.php" novalidate data-check>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
                <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>

                <?php if ($step === 'email'): /* ---- Step 1: email of the account ---- */ ?>
                    <input type="hidden" name="action" value="email">
                    <div class="field">
                        <label for="email"><?= e($t['label_email']) ?></label>
                        <div class="input<?= $inv('email') ?>">
                            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" maxlength="60"
                                   placeholder="<?= e($t['ph_email']) ?>" value="<?= e($old['email']) ?>" required data-rule="email" data-msg="<?= e($t['err_email_invalid']) ?>">
                        </div>
                        <?= $err('email') ?>
                    </div>
                    <p class="hint"><?= e($t['fp_no_email_note']) ?></p>
                    <button class="btn-submit" type="submit"><span><?= e($t['btn_next']) ?></span><?= $arrow ?></button>

                <?php elseif ($step === 'otp'): /* ---- Step 2: the code from the email (resend / change email) ---- */ ?>
                    <input type="hidden" name="action" value="otp">
                    <div class="field">
                        <label for="otp"><?= e($t['label_otp']) ?></label>
                        <div class="input<?= $inv('otp') ?>">
                            <?= $lock ?>
                            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" data-digits
                                   class="otp" placeholder="<?= e($t['ph_otp']) ?>" autofocus required data-rule="otp" data-msg="<?= e($t['err_otp_format']) ?>">
                        </div>
                        <?= $err('otp') ?>
                    </div>
                    <button class="btn-submit" type="submit"><span><?= e($t['btn_verify']) ?></span><?= $arrow ?></button>
                    <div class="row">
                        <button class="link-btn" type="submit" name="action" value="resend" formnovalidate><?= e($t['btn_resend']) ?></button>
                        <a class="link" href="forgot-password.php?restart=1"><?= e($t['fp_change_email']) ?></a>
                    </div>

                <?php else: /* ---- Step 3: new password twice ---- */ ?>
                    <input type="hidden" name="action" value="new">
                    <?php foreach (['new' => ['cp_new', 'ph_password'], 'again' => ['cp_again', 'cp_ph_again']] as $name => $f): ?>
                    <div class="field">
                        <label for="<?= $name ?>"><?= e($t[$f[0]]) ?></label>
                        <div class="input<?= $inv($name) ?>">
                            <?= $lock ?>
                            <input id="<?= $name ?>" name="<?= $name ?>" type="password" autocomplete="new-password" maxlength="128" placeholder="<?= e($t[$f[1]]) ?>" required
                                   data-rule="<?= $name === 'new' ? 'password' : 'same:new' ?>" data-msg="<?= e($name === 'new' ? $t['err_password_rule'] : $t['cp_err_again']) ?>">
                            <button type="button" class="toggle" aria-pressed="false" aria-label="<?= e($t['show_password']) ?>"><?php view('partials/eye-icons'); ?></button>
                        </div>
                        <?= $err($name) ?>
                    </div>
                    <?php endforeach; ?>
                    <p class="hint"><?= e($t['cp_rule']) ?></p>
                    <button class="btn-submit" type="submit"><span><?= e($t['fp_save_new']) ?></span><?= $arrow ?></button>
                <?php endif; ?>

                <p class="signup"><a class="link" href="login.php"><?= e($t['fp_back_login']) ?></a></p>
            </form>
        </section>
    </main>
</div>
<script src="<?= e(asset('assets/js/validation.js')) ?>"></script>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
