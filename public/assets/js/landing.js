// landing.js - switches the page between Bangla and English.
// Only the 2 buttons (data-i18n="key") and the service names (data-bn / data-en, from the database) change.
// Everything else on the page always stays Bangla.

(function () {
    var texts = window.LANG_TEXTS;                         // { bn: {...}, en: {...} }
    var buttons = document.querySelectorAll('[data-lang]');
    var items = document.querySelectorAll('[data-i18n]');
    var names = document.querySelectorAll('[data-bn]');     // service names: both languages are on the element

    function setLanguage(lang) {
        if (!texts || !texts[lang]) { return; }

        // change every text on the page
        for (var i = 0; i < items.length; i++) {
            var key = items[i].getAttribute('data-i18n');
            if (texts[lang][key] !== undefined) {
                items[i].textContent = texts[lang][key];
            }
        }

        for (var n = 0; n < names.length; n++) {
            names[n].textContent = names[n].getAttribute('data-' + lang) || names[n].textContent;
        }

        // highlight the active button
        for (var j = 0; j < buttons.length; j++) {
            var isActive = buttons[j].getAttribute('data-lang') === lang;
            buttons[j].setAttribute('aria-pressed', isActive ? 'true' : 'false');
        }

        // remember the choice for 1 year (PHP reads this cookie)
        document.cookie = 'lang=' + lang + '; path=/; max-age=31536000';
    }

    // PHONE: the switch is a dropdown. Tap it to open, tap the other language to change.
    var box = document.querySelector('.lang-switch');
    function isPhone() { return window.matchMedia('(max-width: 899px)').matches; }

    for (var k = 0; k < buttons.length; k++) {
        buttons[k].addEventListener('click', function (ev) {
            var active = this.getAttribute('aria-pressed') === 'true';
            if (isPhone() && active) {                 // tap on the shown language = open / close
                box.classList.toggle('open');
                ev.stopPropagation();
                return;
            }
            setLanguage(this.getAttribute('data-lang'));
            box.classList.remove('open');
        });
    }
    box.addEventListener('click', function (ev) {      // tap on the pill / arrow also opens it
        if (isPhone() && ev.target === box || ev.target.closest('.chev') || ev.target.closest('.ic')) {
            box.classList.toggle('open');
        }
    });
    document.addEventListener('click', function (ev) { // tap outside = close
        if (!box.contains(ev.target)) { box.classList.remove('open'); }
    });
})();
