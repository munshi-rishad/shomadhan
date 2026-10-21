<?php
// app/views/partials/user-tabbar.php
// PHONE bottom bar of the user pages (user/home.php). On PC it is hidden; the left menu has the same pages.
// view('partials/user-tabbar', ['t' => $t, 'on' => 'home' | 'bookings' | 'wallet' | 'messages' | 'profile', 'msgUnread' => n])

// Tabs: key => [address, text key, icon paths]
$tabs = [
    'home'     => ['user-dashboard.php', 'tab_home',     '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h5v-6h4v6h5V10"/>'],
    'bookings' => ['bookings.php',       'tab_bookings', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 15l2.5 2.5L16 13"/>'],
    'wallet'   => ['wallet.php',         'tab_wallet',   '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"/><path d="M21 9h-5a3 3 0 0 0 0 6h5z"/>'],
    'messages' => ['messages.php',       'tab_messages', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>'],
    'profile'  => ['profile.php',        'tab_profile',  '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>'],
];
$msgUnread = (int)($msgUnread ?? 0);     // red count on the Messages tab (9+ when more)
?>
<nav class="tabbar" aria-label="<?= e($t['side_menu']) ?>">
    <?php foreach ($tabs as $k => $tb): ?>
        <a href="<?= e($tb[0]) ?>"<?= $on === $k ? ' class="on" aria-current="page"' : '' ?>>
            <span class="tab-ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><?= $tb[2] ?></svg><?php if ($k === 'messages' && $msgUnread > 0): ?><span class="count"><?= $msgUnread > 9 ? '9+' : $msgUnread ?></span><?php endif; ?></span><span><?= e($t[$tb[1]]) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
