/* form-check.js - loaded on every page with a form: eye buttons for passwords + checks forms before they are sent (the server checks again, always).
   A form with data-check is checked. Each field to check has:
     data-rule="name | mobile | gmail | email | password | otp | same:<field name>"
     data-msg="error text"          (shown under the field)
     required                        (empty is an error; without it, empty is allowed) */
(function () {
    'use strict';
    // ---- eye buttons: show / hide the password next to it (every page with a password box)
    [].slice.call(document.querySelectorAll('.toggle')).forEach(function (toggle) {
        var input = toggle.closest('.input').querySelector('input');
        toggle.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });
    var RULES = {
        name:     function (v) { return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v.replace(/\s+/g, ' ').trim()); },
        mobile:   function (v) { return /^01[3-9]\d{8}$/.test(v.trim()); },
        gmail:    function (v) { return /^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/.test(v.trim().toLowerCase()); },
        email:    function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) && v.trim().length <= 60; },
        password: function (v) { return /^[\x21-\x7E]{8,128}$/.test(v); },
        otp:      function (v) { return /^\d{6}$/.test(v.trim()); }
    };

    function box(el) { return el.closest('.field') || el.closest('.f') || el.parentNode; }

    function errEl(el) {
        var b = box(el), p = b.querySelector('.error-text');
        if (!p) {
            p = document.createElement('p');
            p.className = 'error-text';
            p.setAttribute('role', 'alert');
            b.appendChild(p);
        }
        return p;
    }

    function mark(el, msg) {
        var wrap = el.closest('.input') || el;
        var p = errEl(el);
        if (msg) {
            p.textContent = msg; p.hidden = false;
            wrap.classList.add('invalid'); el.setAttribute('aria-invalid', 'true');
        } else {
            p.textContent = ''; p.hidden = true;
            wrap.classList.remove('invalid'); el.removeAttribute('aria-invalid');
        }
    }

    function check(el) {
        var v = el.value || '', rule = el.getAttribute('data-rule') || '', ok = true;
        if (v.trim() === '') {
            ok = !el.required;
        } else if (rule.indexOf('same:') === 0) {
            var other = el.form.elements[rule.slice(5)];
            ok = !!other && other.value === v;
        } else if (RULES[rule]) {
            ok = RULES[rule](v);
        }
        mark(el, ok ? '' : (el.getAttribute('data-msg') || ''));
        return ok;
    }

    [].slice.call(document.querySelectorAll('form[data-check]')).forEach(function (form) {
        var fields = [].slice.call(form.querySelectorAll('[data-rule]'));
        form.addEventListener('submit', function (ev) {
            var by = ev.submitter;
            if (by && by.hasAttribute('formnovalidate')) { return; }
            var first = null;
            fields.forEach(function (el) { if (!el.disabled && !check(el) && !first) { first = el; } });
            if (first) { ev.preventDefault(); ev.stopImmediatePropagation(); first.focus(); }
        });
        fields.forEach(function (el) {
            var ev = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(ev, function () { if (el.getAttribute('aria-invalid')) { check(el); } });
            el.addEventListener('blur', function () { if (el.value !== '') { check(el); } });
        });
    });
})();
