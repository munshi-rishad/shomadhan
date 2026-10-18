<?php // app/views/dashboard/blank.php - blank dashboard (needs $title, $account, $notice) ?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($title) ?> | Shomadhan</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth.css')) ?>">
</head>
<body>
<div class="page">
    <main class="auth">
        <?php if ($notice !== ''): ?><p class="alert alert-ok" role="status"><?= e($notice) ?></p><?php endif; ?>
        <h1 class="dash-title"><?= e($title) ?></h1>
        <p class="dash-name"><?= e($account['name']) ?></p>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button class="btn-submit" type="submit"><span><?= e($t['btn_logout']) ?></span></button>
        </form>
    </main>
</div>
</body>
</html>
