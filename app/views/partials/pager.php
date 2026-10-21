<?php
// app/views/partials/pager.php
// "← Previous   Page 2 of 5   Next →" under the long admin lists (bookings, topups, users / employees / admins / providers).
// Shown only when there is more than one page.
// view('partials/pager', ['t' => $t, 'p' => current page, 'pages' => page count, 'url' => function (array $set) -> address])
// $url is the list's own link maker, so the search, tab and sort stay the same when the page changes.
?>
<?php if ($pages > 1): ?>
<nav class="pager" aria-label="pages">
    <?php if ($p > 1): ?><a class="btn btn-small btn-ghost" href="<?= e($url(['p' => $p - 1])) ?>">← <?= e($t['adm_prev']) ?></a><?php endif; ?>
    <span><?= e(sprintf($t['adm_page_of'], $p, $pages)) ?></span>
    <?php if ($p < $pages): ?><a class="btn btn-small btn-ghost" href="<?= e($url(['p' => $p + 1])) ?>"><?= e($t['adm_next']) ?> →</a><?php endif; ?>
</nav>
<?php endif; ?>
