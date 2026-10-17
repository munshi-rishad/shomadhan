// landing.js - switches the page between Bangla and English.
// Only elements with data-i18n="key" change (service names + the 2 buttons).
// Everything else on the page always stays Bangla.

(function () {
    var texts = window.LANG_TEXTS;                         // { bn: {...}, en: {...} }
    var buttons = document.querySelectorAll('[data-lang]');
    var items = document.querySelectorAll('[data-i18n]');

    function setLanguage(lang) {
        if (!texts || !texts[lang]) { return; }

        // change every text on the page
        for (var i = 0; i < items.length; i++) {
            var key = items[i].getAttribute('data-i18n');
            if (texts[lang][key] !== undefined) {
                items[i].textContent = texts[lang][key];
            }
        }

        // highlight the active button
        for (var j = 0; j < buttons.length; j++) {
            var isActive = buttons[j].getAttribute('data-lang') === lang;
            buttons[j].setAttribute('aria-pressed', isActive ? 'true' : 'false');
        }

        // remember the choice for 1 year (PHP reads this cookie)
        document.cookie = 'lang=' + lang + '; path=/; max-age=31536000';
    }

    for (var k = 0; k < buttons.length; k++) {
        buttons[k].addEventListener('click', function () {
            setLanguage(this.getAttribute('data-lang'));
        });
    }
})();
