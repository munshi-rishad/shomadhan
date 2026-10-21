<?php
// app/views/partials/lang-switch.php
// The বাংলা / English buttons in the top bar (dashboards, user pages, login pages, info pages).
// assets/js/lang.js must be on the page: it saves the "lang" cookie and reloads.
// aria-pressed marks the language in use. Needs: $lang
?>
<div class="lang" role="group" aria-label="Language / ভাষা">
    <button type="button" data-lang="bn" aria-pressed="<?= $lang === 'bn' ? 'true' : 'false' ?>">বাংলা</button>
    <button type="button" data-lang="en" aria-pressed="<?= $lang === 'en' ? 'true' : 'false' ?>">English</button>
</div>
