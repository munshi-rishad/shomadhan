// validation.js - login form: show/hide password + quick check in the browser.
// The server checks again (LoginValidator.php), so this is only for speed.
(function () {
    var form = document.querySelector('.form');
    if (!form) { return; }

    var mobile = form.querySelector('#mobile');
    var pass = form.querySelector('#password');
    var toggle = form.querySelector('.toggle');

    // eye button
    toggle.addEventListener('click', function () {
        var show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
    });

    function setError(input, id, message) {
        var box = input.closest('.input');
        var text = document.getElementById(id);
        box.classList.toggle('invalid', !!message);
        text.textContent = message || '';
        text.hidden = !message;
        return !message;
    }

    // Same rules as AuthController::validateLogin() (server checks again)
    function mobileMessage() {
        var v = mobile.value.trim();
        if (v === '') { return form.getAttribute('data-err-mobile-empty'); }
        if (!/^\d{11}$/.test(v)) { return form.getAttribute('data-err-mobile-length'); }
        if (!/^01[3-9]\d{8}$/.test(v)) { return form.getAttribute('data-err-mobile-format'); }
        return '';
    }

    function passwordMessage() {
        var n = Array.from(pass.value).length;       // counts Bangla letters correctly
        if (n === 0) { return form.getAttribute('data-err-password-empty'); }
        if (n < 8) { return form.getAttribute('data-err-password-min'); }
        if (n > 128) { return form.getAttribute('data-err-password-max'); }
        return '';
    }

    // mobile box: only digits, max 11
    mobile.addEventListener('input', function () {
        mobile.value = mobile.value.replace(/\D/g, '').slice(0, 11);
    });

    form.addEventListener('submit', function (ev) {
        var a = setError(mobile, 'err-mobile', mobileMessage());
        var b = setError(pass, 'err-password', passwordMessage());
        if (!a || !b) { ev.preventDefault(); (a ? pass : mobile).focus(); }
    });
})();
