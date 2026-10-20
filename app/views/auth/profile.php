<?php
// app/views/auth/profile.php - profile page for users (admin + employee see the same cards inside their dashboard):
// left = photo letter, name and all info; right = edit my info + change password
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<?php view('partials/head', ['title' => $t['pf_title'] . ' | Shomadhan', 'css' => 'profile']); ?>
</head>
<body>
<header class="top">
    <a class="page-back" href="<?= e($back) ?>">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?>
    </a>
    <img class="top-logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
    <div class="top-right">
        <?php view('partials/lang-switch', ['lang' => $lang]); ?>
        <?php view('partials/logout-form', ['t' => $t, 'class' => 'top-out']); ?>
    </div>
</header>

<main class="wrap">
    <h1 class="page-h1"><?= e($t['pf_title']) ?></h1>
    <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><p class="alert alert-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>

    <div class="grid">
        <!-- left: who am I -->
        <section class="card me">
            <?php view('partials/profile-info', ['t' => $t, 'account' => $account, 'action' => 'profile.php', 'photoError' => $errors['photo'] ?? '']); ?>
        </section>

        <div class="col">
            <?php view('partials/profile-forms', ['t' => $t, 'account' => $account, 'errors' => $errors, 'box' => $box,
                'old' => $old, 'areas' => $areas, 'action' => 'profile.php']); ?>
        </div>
    </div>
</main>
<script src="<?= e(asset('assets/js/form-check.js')) ?>"></script>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
<script src="<?= e(asset('assets/js/zoom.js')) ?>"></script>
</body>
</html>
