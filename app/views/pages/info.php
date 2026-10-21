<?php
// app/views/pages/info.php - about.php, terms.php, privacy.php
// The About / Terms / Privacy pages, open to everyone. Texts from Admin -> "সাইট তথ্য".
// Rendered by PageController::show(). Gets: $t, $lang, $page ('about' | 'terms' | 'privacy'), $title, $site,
// $home (logo / back link: my dashboard when logged in, else the welcome page), $account (or null), $stats (about page).

// The three pages and their title keys; the text of terms / privacy
$pages = ['about' => 'page_about', 'terms' => 'terms_link1', 'privacy' => 'terms_link2'];
$text  = $page === 'about' ? '' : site_val($site, $page, $lang);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" class="u-page">
<head>
<?php view('partials/head', ['title' => $title . ' | Shomadhan', 'css' => 'pages']); ?>
</head>
<body>
<div class="page info-page">
    <?php /* ---- Top bar: logo, links to the other two pages, contact, language, login / my dashboard ---- */ ?>
    <header class="ip-top">
        <a class="ip-brand" href="<?= e($home) ?>"><img src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan"></a>
        <nav class="ip-nav">
            <?php foreach ($pages as $p => $k): if ($p === $page) { continue; } // this page's name is the big title below ?>
                <a href="<?= $p ?>.php"><?= e($t[$k]) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="ip-tools">
            <?php view('partials/site-contact', ['site' => $site, 'lang' => $lang, 't' => $t, 'items' => ['phone', 'email', 'hours']]); ?>
            <?php view('partials/lang-switch', ['lang' => $lang]); ?>
            <a class="btn btn-small" href="<?= e($account ? $home : 'login.php') ?>"><?= e($t[$account ? 'page_my_dashboard' : 'btn_login']) ?></a>
        </div>
    </header>

    <?php /* ---- Green band: back, title, one line about the page ---- */ ?>
    <section class="ip-hero">
        <div class="ip-hero-in">
            <a class="ip-back" href="<?= e($home) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><?= e($t['login_back']) ?></a>
            <h1><?= e($title) ?></h1>
            <p><?= e($t['page_sub_' . $page]) ?></p>
        </div>
    </section>

    <main class="ip-main ip-<?= e($page) ?>">
        <?php if ($page === 'about'): /* ---- About: story + mission, numbers, why us ---- */ ?>
            <div class="ip-pair">
                <section class="ip-card">
                    <span class="ip-ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2zM4 19V5M8 7h6M8 11h6"/></svg></span>
                    <h2><?= e($t['page_story']) ?></h2>
                    <div class="ip-text"><?= rich_text(site_val($site, 'about', $lang)) ?></div>
                </section>
                <section class="ip-card ip-mission">
                    <span class="ip-ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg></span>
                    <h2><?= e($t['page_mission']) ?></h2>
                    <div class="ip-text"><?= rich_text(site_val($site, 'mission', $lang)) ?></div>
                </section>
            </div>
            <?php /* real numbers from the database */ ?>
            <section class="ip-stats">
                <?php foreach (['categories' => 'page_n_categories', 'services' => 'page_n_services', 'providers' => 'page_n_providers', 'areas' => 'page_n_areas'] as $k => $label): ?>
                    <div><b><?= e(number_format((int)($stats[$k] ?? 0))) ?>+</b><span><?= e($t[$label]) ?></span></div>
                <?php endforeach; ?>
            </section>
            <section class="ip-why">
                <h2><?= e($t['page_why']) ?></h2>
                <?php view('partials/features', ['t' => $t]); ?>
            </section>
        <?php else: /* ---- Terms / Privacy: one text ---- */ ?>
            <div class="ip-doc-wrap">
                <article class="ip-card ip-doc"><div class="ip-text"><?= rich_text($text) ?></div></article>
            </div>
        <?php endif; ?>
    </main>

    <?php /* ---- Footer (no page links: they are in the top bar) ---- */ ?>
    <?php view('partials/site-footer', ['site' => $site, 'lang' => $lang, 't' => $t, 'links' => false]); ?>
</div>
<script src="<?= e(asset('assets/js/lang.js')) ?>"></script>
</body>
</html>
