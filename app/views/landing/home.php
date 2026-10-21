<?php
// app/views/landing/home.php - index.php
// The welcome page for visitors: logo + headline, new-account coupon picture, service icons, register / login buttons.
// Rendered by HomeController. Gets: $lang, $t (chosen language: only service names + the 2 buttons),
// $bn (Bangla texts: the rest of the page is always Bangla at first), $switch (texts landing.js swaps on the
// language buttons, both languages), $services, $promo (coupon picture), $site.
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<?php view('partials/head', ['title' => $bn['page_title'], 'css' => 'landing']); ?>
</head>
<body>

<div class="page">
    <?php /* ---- Background photo + soft white haze (see landing.css) ---- */ ?>
    <div class="bg" aria-hidden="true"></div>

    <?php /* ---- Language buttons, top right (own markup with a globe icon; landing.js switches the texts without a reload).
             Contact, About / Terms / Privacy are in the footer below ---- */ ?>
    <div class="lang-switch" role="group" aria-label="Language / ভাষা">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>
        <button type="button" data-lang="bn" aria-pressed="<?= $lang === 'bn' ? 'true' : 'false' ?>">বাংলা</button>
        <button type="button" data-lang="en" aria-pressed="<?= $lang === 'en' ? 'true' : 'false' ?>">English</button>
        <svg class="ic chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </div>

    <main class="layout">

        <?php /* ---- 1) HERO: logo + headline ---- */ ?>
        <section class="hero">
            <a class="hero-logo" href="index.php"><img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan"></a>
            <p class="tagline"><?= e($bn['tagline']) ?></p>
            <h1>
                <span><?= e($bn['h1_line1']) ?></span>
                <span class="green"><?= e($bn['h1_line2']) ?></span>
            </h1>
            <hr class="rule">
            <p class="subtitle"><?= e($bn['subtitle']) ?></p>
        </section>

        <?php /* ---- 2) COUPON card + service icons ---- */ ?>
        <section class="info">

            <?php /* New-account coupon banner (same image on phone and PC; Admin -> welcome page) */ ?>
            <div class="promo">
                <img src="<?= e(asset($promo)) ?>" alt="<?= e($bn['promo_alt']) ?>">
            </div>

            <?php /* Service icons = the first services the admin made (admin dashboard -> Services) */ ?>
            <ul class="services" id="services">
                <?php foreach ($services as $service): ?>
                    <li>
                        <span class="tile tile-<?= e($service['color']) ?>">
                            <img src="<?= e(asset($service['icon'])) ?>" alt="">
                        </span>
                        <span class="label" data-bn="<?= e($service['name_bn']) ?>" data-en="<?= e($service['name_en']) ?>"><?= e($service['name_' . $lang]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

        </section>

        <?php /* ---- 3) BUTTONS: register / login ---- */ ?>
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
<?php view('partials/public-footer', ['t' => $t, 'lang' => $lang]); ?>

<?php /* ---- Both languages are given to JavaScript, so the switch is instant ---- */ ?>
<script>window.LANG_TEXTS = <?= json_encode($switch, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= e(asset('assets/js/landing.js')) ?>"></script>
</body>
</html>
