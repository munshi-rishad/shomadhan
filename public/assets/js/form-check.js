/* form-check.js - loaded on every page with a form: eye buttons for passwords + checks forms before they are sent
   (the server checks again, always).
   A form with data-check is checked. Each field to check has:
     data-rule="name | mobile | gmail | email | password | otp | same:<field name>
                | range (number between its min / max) | minlen (data-min letters at least)
                | photo (picture file: data-msg-type, data-msg-big + data-max-bytes,
                         data-msg-square + data-min-px = square and at least that many pixels)"
     data-msg="error text"          (shown under the field)
     required                        (empty is an error; without it, empty is allowed;
                                      a required field without data-rule is checked too, e.g. a dropdown)
     data-unique="mobile | email"    (live AJAX check: already used by another account? needs data-unique-url on the form,
                                      + data-unique-kind="account|provider", data-unique-role / data-unique-id = the row being edited)
   A submit button with formnovalidate skips the checks. */
(function () {
    'use strict';

    // ---- Eye buttons: show / hide the password next to it (every page with a password box) ----
    [].slice.call(document.querySelectorAll('.toggle')).forEach(function (toggle) {
        var input = toggle.closest('.input').querySelector('input');
        toggle.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    // ---- The rules a field can name in data-rule (true = the value is fine) ----
    var RULES = {
        name: function (v) {
            return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v.replace(/\s+/g, ' ').trim());
        },
        mobile: function (v) {
            return /^01[3-9]\d{8}$/.test(v.trim());
        },
        gmail: function (v) {
            return /^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/.test(v.trim().toLowerCase());
        },
        email: function (v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) && v.trim().length <= 60;
        },
        password: function (v) {
            return /^[\x21-\x7E]{8,128}$/.test(v);
        },
        otp: function (v) {
            return /^\d{6}$/.test(v.trim());
        },
        range: function (v, el) {
            var n = Number(v);
            return /^\d+$/.test(v.trim()) && n >= Number(el.min) && n <= Number(el.max);
        },
        minlen: function (v, el) {
            return v.replace(/\s+/g, ' ').trim().length >= Number(el.getAttribute('data-min') || 1);
        },
        photo: function (v, el) {
            return !el._bad;      // set when the file was chosen (see "Picture files" below)
        }
    };

    // ---- The box around a field (where its error text goes) ----
    function box(el) {
        return el.closest('.field') || el.closest('.f') || el.parentNode;
    }

    // ---- The error text under a field; made the first time it is needed ----
    function errEl(el) {
        var fieldBox = box(el);
        var errorText = fieldBox.querySelector('.error-text');
        if (!errorText) {
            errorText = document.createElement('p');
            errorText.className = 'error-text';
            errorText.setAttribute('role', 'alert');
            fieldBox.appendChild(errorText);
        }
        return errorText;
    }

    // ---- Show an error on a field (msg), or clear it (msg = '') ----
    function mark(el, msg) {
        var wrap = el.closest('.input') || el;
        var errorText = errEl(el);
        if (msg) {
            errorText.textContent = msg;
            errorText.hidden = false;
            wrap.classList.add('invalid');
            el.setAttribute('aria-invalid', 'true');
        } else {
            errorText.textContent = '';
            errorText.hidden = true;
            wrap.classList.remove('invalid');
            el.removeAttribute('aria-invalid');
        }
    }

    // ---- Check one field against its rule; shows / clears its error. true = fine ----
    function check(el) {
        var value = el.value || '';
        var rule = el.getAttribute('data-rule') || '';
        var ok = true;
        if (value.trim() === '') {
            ok = !el.required;
        } else if (rule.indexOf('same:') === 0) {
            var other = el.form.elements[rule.slice(5)];
            ok = !!other && other.value === value;
        } else if (RULES[rule]) {
            ok = RULES[rule](value, el);
        }
        if (!ok && el._bad) {       // a picture with a problem: its own message
            mark(el, el._bad);
            return false;
        }
        if (ok && el._taken) {      // live check said: already used
            mark(el, el._taken);
            return false;
        }
        mark(el, ok ? '' : (el.getAttribute('data-msg') || ''));
        return ok;
    }

    [].slice.call(document.querySelectorAll('form[data-check]')).forEach(function (form) {
        var fields = [].slice.call(form.querySelectorAll('[data-rule], [required]'));

        // ---- Picture files: right type, not too big, square and big enough (read in the browser) ----
        fields.filter(function (el) { return el.getAttribute('data-rule') === 'photo'; }).forEach(function (el) {
            el.addEventListener('change', function () {
                var file = el.files && el.files[0];
                el._bad = '';
                if (!file) {
                    check(el);
                    return;
                }
                if (['image/png', 'image/jpeg', 'image/webp'].indexOf(file.type) < 0) {
                    el._bad = el.getAttribute('data-msg-type');
                } else if (file.size > Number(el.getAttribute('data-max-bytes') || Infinity)) {
                    el._bad = el.getAttribute('data-msg-big');
                }
                if (el._bad || !window.URL) {
                    check(el);
                    return;
                }
                var img = new Image();
                var src = URL.createObjectURL(file);
                img.onload = function () {
                    var w = img.naturalWidth;
                    var h = img.naturalHeight;
                    var min = Number(el.getAttribute('data-min-px') || 0);
                    var pick = el.closest('.photo-pick');
                    var face = pick && pick.querySelector('.pf-avatar');
                    if (w !== h || w < min) {
                        var parts = [min, min, w, h];
                        el._bad = (el.getAttribute('data-msg-square') || '').replace(/%d/g, function () { return parts.shift(); });
                        URL.revokeObjectURL(src);
                    } else if (face) {      // a good photo: show it in the round picture next to the field
                        var shown = document.createElement('img');
                        shown.className = 'pf-avatar';
                        shown.alt = '';
                        shown.src = src;
                        face.replaceWith(shown);
                    }
                    check(el);
                };
                img.onerror = function () {
                    el._bad = el.getAttribute('data-msg-type');
                    URL.revokeObjectURL(src);
                    check(el);
                };
                img.src = src;
            });
        });

        // ---- On send: check every field; stop at the first wrong one ----
        form.addEventListener('submit', function (ev) {
            var by = ev.submitter;
            if (by && by.hasAttribute('formnovalidate')) {
                return;
            }
            var first = null;
            fields.forEach(function (el) {
                if (!el.disabled && !check(el) && !first) {
                    first = el;
                }
            });
            if (first) {
                ev.preventDefault();
                ev.stopImmediatePropagation();
                first.focus();
            }
        });

        // ---- LIVE CHECK (AJAX): mobile / email already used by another account? (the server checks again on save) ----
        var url = form.getAttribute('data-unique-url');
        var csrf = form.querySelector('[name="csrf"]');
        if (url) {
            [].slice.call(form.querySelectorAll('[data-unique]')).forEach(function (el) {
                var timer = null;
                var asked = '';     // the last value sent to the server

                function ask() {
                    var value = el.value.trim().toLowerCase();
                    if (value === '' || value === asked || !check(el)) {     // only a correct-looking value is asked
                        return;
                    }
                    asked = value;
                    var body = new URLSearchParams();
                    body.append('field', el.getAttribute('data-unique'));
                    body.append('value', value);
                    body.append('kind', form.getAttribute('data-unique-kind') || 'account');
                    body.append('role', form.getAttribute('data-unique-role') || '');
                    body.append('id', form.getAttribute('data-unique-id') || '0');
                    body.append('csrf', csrf ? csrf.value : '');
                    fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (!data || !data.ok || el.value.trim().toLowerCase() !== value) {   // typed again meanwhile
                                return;
                            }
                            el._taken = data.available ? '' : data.message;
                            check(el);
                        })
                        .catch(function () {
                            asked = '';
                        });
                }

                // ask a short time after the typing stops, or when the field is left
                el.addEventListener('input', function () {
                    if (el._taken) {
                        el._taken = '';
                        check(el);
                    }
                    asked = '';
                    clearTimeout(timer);
                    timer = setTimeout(ask, 450);
                });
                el.addEventListener('blur', function () {
                    clearTimeout(timer);
                    ask();
                });
            });
        }

        // ---- While typing: a field with an error is checked again; leaving a filled field checks it ----
        fields.forEach(function (el) {
            var eventName = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(eventName, function () {
                if (el.getAttribute('aria-invalid')) {
                    check(el);
                }
            });
            el.addEventListener('blur', function () {
                if (el.value !== '') {
                    check(el);
                }
            });
            if (el.tagName === 'SELECT') {      // a dropdown is checked as soon as something is picked
                el.addEventListener('change', function () { check(el); });
            }
        });
    });
})();
