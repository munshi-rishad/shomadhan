<?php // app/views/partials/me-chip.php - top-right chip: my picture + name + role, opens my profile (same for every role). Needs $t, $account, $href, $on ?>
<a class="me-chip<?= !empty($on) ? ' on' : '' ?>" href="<?= e($href) ?>" title="<?= e($t['pf_menu']) ?>">
    <?php view('partials/avatar', ['name' => $account['name'] ?? '?', 'photo' => $account['photo'] ?? null, 'class' => 'av-sm']); ?>
    <span><b><?= e($account['name'] ?? '') ?></b><small><?= e($t['pf_role_' . ($account['role'] ?? 'user')]) ?></small></span>
</a>
