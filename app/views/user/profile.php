<?php
// app/views/user/profile.php - profile.php
// The user's profile. Pulled into the user page frame (user/home.php, $mode = 'profile') by ProfileController.
// The page itself is partials/profile-page.php: the SAME profile page as admin / employee
// (users also get favourites, help, terms / about and log out in their list).
// Gets: $user, $errors, $old, $box, $areas, $favs (hearted sub-services) + the frame's $t, $site, $card.
view('partials/profile-page', [
    't'       => $t,
    'account' => $user + ['role' => 'user'],
    'errors'  => $errors,
    'old'     => $old,
    'box'     => $box,
    'areas'   => $areas,
    'action'  => 'profile.php',
    'site'    => $site,
    'favs'    => $favs,
    'card'    => $card,
]);
