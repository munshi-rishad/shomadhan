<?php
// app/views/partials/me-chip.php
// Top-right chip: my picture + name + role. Clicking it opens my profile. Same for every role
// (admin layout and the user pages). Needs: $t, $account, $href (profile address), $on (true = profile page is open)
?>
<a class="me-chip<?= !empty($on) ? ' on' : '' ?>" href="<?= e($href) ?>" title="<?= e($t['pf_menu']) ?>">
    <?php view('partials/avatar', ['name' => $account['name'] ?? '?', 'photo' => $account['photo'] ?? null, 'class' => 'av-sm']); ?>
    <span><b><?= e($account['name'] ?? '') ?></b><small><?= e($t['pf_role_' . ($account['role'] ?? 'user')]) ?></small></span>
</a>
