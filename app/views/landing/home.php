<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($bn['page_title']) ?></title>

    <!-- Fonts: Hind Siliguri = Bangla, Poppins = stylish English letters and numbers -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<div class="page">
    <!-- Background photo + soft white haze (see landing.css) -->
    <div class="bg" aria-hidden="true"></div>

    <!-- Language buttons: press one to change the language -->
    <div class="lang-switch" role="group" aria-label="Language / ভাষা">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>
        <button type="button" data-lang="bn" aria-pressed="<?= $lang === 'bn' ? 'true' : 'false' ?>">বাংলা</button>
        <button type="button" data-lang="en" aria-pressed="<?= $lang === 'en' ? 'true' : 'false' ?>">English</button>
        <svg class="ic chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </div>

    <main class="layout">

        <!-- 1) HERO: logo + headline -->
        <section class="hero">
            <img class="logo" src="assets/images/logo.png" alt="Shomadhan">
            <p class="tagline"><?= e($bn['tagline']) ?></p>
            <h1>
                <span><?= e($bn['h1_line1']) ?></span>
                <span class="green"><?= e($bn['h1_line2']) ?></span>
            </h1>
            <hr class="rule">
            <p class="subtitle"><?= e($bn['subtitle']) ?></p>
        </section>

        <!-- 2) COUPON card + service icons -->
        <section class="info">

            <!-- New-account coupon banner (same image on phone and PC) -->
            <div class="promo">
                <img src="assets/images/coupon-banner.jpg" width="1600" height="616" alt="<?= e($bn['promo_alt']) ?>">
            </div>

            <!-- The 5 service icons come from ServiceModel (a loop) -->
            <ul class="services">
                <?php foreach ($services as $service): ?>
                    <li>
                        <span class="tile tile-<?= e($service['color']) ?>">
                            <img src="assets/images/icons/<?= e($service['icon']) ?>" alt="">
                        </span>
                        <span class="label" data-i18n="<?= e($service['key']) ?>"><?= e($t[$service['key']]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

        </section>

        <!-- 3) BUTTONS: register / login -->
        <section class="actions">
            <a class="btn btn-fill" href="register.php">
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M19 8v6M16 11h6"/></svg>
                <span data-i18n="btn_register"><?= e($t['btn_register']) ?></span>
                <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
            </a>

            <a class="btn btn-outline" href="login.php">
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5M4 12h11M11 8l4 4-4 4"/></svg>
                <span data-i18n="btn_login"><?= e($t['btn_login']) ?></span>
                <svg class="ic arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
            </a>
        </section>

    </main>
</div>

<!-- Both languages are given to JavaScript, so the switch is instant -->
<script>window.LANG_TEXTS = <?= json_encode($allTexts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="assets/js/landing.js"></script>
</body>
</html>
