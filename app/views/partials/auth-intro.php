<?php
// app/views/partials/auth-intro.php
// The green "intro" side of the login, register, forgot-password and set-password pages:
// back button, logo + tagline, picture, short text (phone), big heading + feature points + picture (PC).
// view('partials/auth-intro', [
//     't'     => $t,
//     'back'  => 'login.php',            // address of the back link; null = a "log out" button instead (set-password)
//     'illus' => 'login',                // picture: assets/images/<illus>-illustration.svg
//     'sub'   => [line 1, line 2],       // short text under the picture (phone)
//     'h1'    => [first part, green part],
//     'pcSub' => [line 1, line 2],       // optional text under the heading (PC, register page)
// ])
$pic = asset('assets/images/' . $illus . '-illustration.svg');
?>
<section class="intro">
    <?php /* ---- Back: a link, or on the set-password page a logout button (that page has nowhere to go back to) ---- */ ?>
    <?php if ($back !== null): ?>
    <a class="back" href="<?= e($back) ?>" aria-label="<?= e($t['login_back']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><span class="back-txt"><?= e($t['login_back']) ?></span></a>
    <?php else: ?>
    <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="back" type="submit" aria-label="<?= e($t['fl_logout']) ?>"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg><span class="back-txt"><?= e($t['fl_logout']) ?></span></button>
    </form>
    <?php endif; ?>

    <?php /* ---- Logo, phone picture + short text ---- */ ?>
    <div class="brand">
        <img class="logo" src="<?= e(asset('assets/images/logo.png')) ?>" alt="Shomadhan">
        <p class="tagline"><?= e($t['tagline']) ?></p>
    </div>
    <img class="illus-m" src="<?= e($pic) ?>" alt="">
    <p class="intro-sub"><?= e($sub[0]) ?><br><?= e($sub[1]) ?></p>

    <?php /* ---- PC: big heading, features, picture ---- */ ?>
    <h1 class="pc-h1"><span><?= e($h1[0]) ?></span><span class="green"><?= e($h1[1]) ?></span></h1>
    <?php if (!empty($pcSub)): ?><p class="pc-sub"><?= e($pcSub[0]) ?><br><?= e($pcSub[1]) ?></p><?php endif; ?>
    <div class="pc-extra">
        <?php view('partials/features', ['t' => $t]); ?>
        <img class="illus-pc" src="<?= e($pic) ?>" alt="">
    </div>
</section>
