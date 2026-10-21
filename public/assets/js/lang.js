/* lang.js - বাংলা / English buttons (partials/lang-switch.php) on every page except the landing page:
   saves the choice in the "lang" cookie (1 year) and reloads the page */
(function () {
    [].slice.call(document.querySelectorAll('.lang [data-lang]')).forEach(function (button) {
        button.addEventListener('click', function () {
            document.cookie = 'lang=' + button.getAttribute('data-lang') + '; path=/; max-age=31536000; samesite=lax';
            location.reload();
        });
    });
})();
