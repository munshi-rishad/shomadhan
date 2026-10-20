/* lang.js - বাংলা / English buttons: saves the choice in the "lang" cookie (1 year) and reloads the page */
(function () {
    [].slice.call(document.querySelectorAll('.lang [data-lang]')).forEach(function (b) {
        b.addEventListener('click', function () {
            document.cookie = 'lang=' + b.getAttribute('data-lang') + '; path=/; max-age=31536000; samesite=lax';
            location.reload();
        });
    });
})();
