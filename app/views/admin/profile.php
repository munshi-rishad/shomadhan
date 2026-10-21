<?php
// app/views/admin/profile.php - ?page=profile
// My profile inside the admin / employee dashboard. The page itself is partials/profile-page.php:
// the SAME profile page the user has (photo + rule, my account list, my info / edit / password parts).
// Shown inside admin/layout.php (AdminController). Gets: $account, $errors, $old, optional $box + the layout's $t, $base.
view('partials/profile-page', [
    't'       => $t,
    'account' => $account,
    'errors'  => $errors,
    'old'     => $old,
    'box'     => $box ?? '',
    'areas'   => AreaModel::getAll(),
    'action'  => $base . '?page=profile',
]);
