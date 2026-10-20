<?php // app/views/partials/lang-switch.php - বাংলা / English buttons (needs assets/js/lang.js on the page) ?>
<div class="lang" role="group" aria-label="Language / ভাষা">
    <button type="button" data-lang="bn" aria-pressed="<?= $lang === 'bn' ? 'true' : 'false' ?>">বাংলা</button>
    <button type="button" data-lang="en" aria-pressed="<?= $lang === 'en' ? 'true' : 'false' ?>">English</button>
</div>
