// validation.js - login + register forms (browser side); the OTP box of forgot / set password too.
// Same rules as AuthController.php (the server checks again).
//  - every box takes ENGLISH keyboard characters only (Bangla letters are removed)
//  - name = CAPITAL letters, email = small letters, mobile = digits only (max 11)
// The error texts come from the form's data-msg (JSON, in the chosen language).
(function () {
    // ---- OTP box: digits only ----
    [].slice.call(document.querySelectorAll('input[data-digits]')).forEach(function (el) {
        el.addEventListener('input', function () {
            el.value = el.value.replace(/\D/g, '').slice(0, 6);
        });
    });

    var form = document.querySelector('.form[data-msg]');   // login + register forms only
    if (!form) {
        return;
    }

    var msg = {};
    try {
        msg = JSON.parse(form.getAttribute('data-msg') || '{}');
    } catch (e) { }

    // a field of this form by its name (null when the page has no such field)
    function byName(fieldName) {
        return form.querySelector('[name="' + fieldName + '"]');
    }
    var name = byName('name');
    var mobile = byName('mobile');
    var email = byName('email');
    var pass = byName('password');
    var area = byName('area');
    var gender = byName('gender');
    var terms = byName('terms');

    // ---- Clean what the person types (keeps the cursor place) ----
    function clean(el, fn) {
        if (!el) {
            return;
        }
        el.addEventListener('input', function () {
            var before = el.value;
            var after = fn(before);
            if (after === before) {
                return;
            }
            var pos = el.selectionStart;
            el.value = after;
            if (pos !== null) {
                try {
                    var newPos = Math.max(0, pos - (before.length - after.length));
                    el.setSelectionRange(newPos, newPos);
                } catch (e) { }
            }
        });
    }
    clean(name, function (v) {
        return v.replace(/[^\x20-\x7E]/g, '').toUpperCase().replace(/[^A-Z .'-]/g, '');
    });
    clean(mobile, function (v) {
        return v.replace(/\D/g, '').slice(0, 11);
    });
    clean(email, function (v) {
        return v.replace(/[^\x21-\x7E]/g, '').toLowerCase();
    });
    clean(pass, function (v) {
        return v.replace(/[^\x21-\x7E]/g, '');
    });

    // ---- Rules: each returns the error text, or '' when OK ----
    function nameMsg() {
        var v = name.value.trim().replace(/\s+/g, ' ');
        if (v === '') {
            return msg.name_empty;
        }
        return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v) ? '' : msg.name_format;
    }
    function mobileLocal() {
        var v = mobile.value.trim();
        if (v === '') {
            return msg.mobile_empty;
        }
        if (!/^\d{11}$/.test(v)) {
            return msg.mobile_length;
        }
        return /^01[3-9]\d{8}$/.test(v) ? '' : msg.mobile_format;
    }
    function emailLocal() {                     // optional, but only Gmail
        var v = email.value.trim().toLowerCase();
        if (v === '') {
            return '';
        }
        return /^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/.test(v) ? '' : msg.email;
    }
    // "already used" answers from the server (register page only, see live check below)
    var taken = { mobile: '', email: '' };
    function mobileMsg() {
        return mobileLocal() || taken.mobile;
    }
    function emailMsg() {
        return emailLocal() || taken.email;
    }
    function passMsg() {
        var v = pass.value;
        if (v === '') {
            return msg.password_empty;
        }
        if (!/^[\x21-\x7E]+$/.test(v)) {
            return msg.password_ascii;
        }
        if (v.length < 8) {
            return msg.password_min;
        }
        return v.length > 128 ? msg.password_max : '';
    }
    function areaMsg() {
        return area.value === '' ? msg.area : '';
    }
    function genderMsg() {
        return gender.value === '' ? msg.gender : '';
    }
    function termsMsg() {
        return terms.checked ? '' : msg.terms;
    }

    // the visible button of a dropdown, from its hidden input
    function comboBtn(hidden) {
        return hidden ? hidden.closest('.combo').querySelector('.combo-btn') : null;
    }

    // ---- The fields this form has: element, id of its error text, rule, box that turns red ----
    var fields = [];
    function add(el, id, fn, box) {
        if (el) {
            fields.push({ el: el, id: id, fn: fn, box: box === undefined ? el.closest('.input') : box });
        }
    }
    add(name, 'err-name', nameMsg);
    add(mobile, 'err-mobile', mobileMsg);
    add(email, 'err-email', emailMsg);
    add(pass, 'err-password', passMsg);
    add(area, 'err-area', areaMsg, comboBtn(area));
    add(gender, 'err-gender', genderMsg, comboBtn(gender));
    add(terms, 'err-terms', termsMsg, null);

    // ---- Check one field and show / clear its error. true = fine ----
    function show(item) {
        var message = item.fn();
        var errorText = document.getElementById(item.id);
        if (item.box) {
            item.box.classList.toggle('invalid', !!message);
        }
        errorText.textContent = message || '';
        errorText.hidden = !message;
        return !message;
    }

    // fix the error live while typing (only a field that already shows an error)
    fields.forEach(function (item) {
        ['input', 'change'].forEach(function (eventName) {
            item.el.addEventListener(eventName, function () {
                if (!document.getElementById(item.id).hidden) {
                    show(item);
                }
            });
        });
    });

    // ---- LIVE CHECK (AJAX): is this mobile / email already used by another account? ----
    // Runs a moment after the person stops typing, only when the format is already correct.
    var checkUrl = form.getAttribute('data-check');
    var csrfBox = form.querySelector('[name="csrf"]');
    function liveCheck(el, field, localFn) {
        if (!checkUrl || !el) {
            return;
        }
        var item = fields.filter(function (i) {
            return i.el === el;
        })[0];
        var timer = null;
        var lastAsked = '';

        function ask() {
            var value = el.value.trim().toLowerCase();
            if (value === '' || localFn() !== '' || value === lastAsked) {
                return;
            }
            lastAsked = value;
            var body = new URLSearchParams();
            body.append('field', field);
            body.append('value', value);
            body.append('csrf', csrfBox ? csrfBox.value : '');
            fetch(checkUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data || !data.ok || el.value.trim().toLowerCase() !== value) {   // typed again meanwhile
                        return;
                    }
                    taken[field] = data.available ? '' : data.message;
                    show(item);
                })
                .catch(function () {
                    lastAsked = '';                 // network problem: server checks on submit
                });
        }

        el.addEventListener('input', function () {
            if (taken[field]) {
                taken[field] = '';
                show(item);
            }
            lastAsked = '';
            clearTimeout(timer);
            timer = setTimeout(ask, 450);
        });
        el.addEventListener('blur', function () {
            clearTimeout(timer);
            ask();
        });
    }
    liveCheck(mobile, 'mobile', mobileLocal);
    liveCheck(email, 'email', emailLocal);

    // ---- On send: check every field; stop and focus the first wrong one ----
    form.addEventListener('submit', function (ev) {
        var first = null;
        fields.forEach(function (item) {
            if (!show(item) && !first) {
                first = item;
            }
        });
        if (first) {
            ev.preventDefault();
            (first.el.type === 'hidden' ? comboBtn(first.el) : first.el).focus();
        }
    });

    // ---- Dropdowns (area has a search box, gender does not) ----
    [].slice.call(form.querySelectorAll('.combo')).forEach(function (combo) {
        var hidden = combo.querySelector('input[type="hidden"]');   // the value that is sent
        var btn = combo.querySelector('.combo-btn');
        var panel = combo.querySelector('.combo-panel');
        var search = combo.querySelector('.combo-search');
        var list = combo.querySelector('.combo-list');
        var items = [].slice.call(list.querySelectorAll('[data-value]'));
        var none = list.querySelector('.combo-empty');
        var text = combo.querySelector('.combo-text');

        // the options left after the search
        function visible() {
            return items.filter(function (li) {
                return !li.hidden;
            });
        }
        // hide the options that don't contain the search text
        function filter() {
            if (!search) {
                return;
            }
            var query = search.value.trim().toLowerCase();
            items.forEach(function (li) {
                li.hidden = li.textContent.toLowerCase().indexOf(query) === -1;
            });
            none.hidden = visible().length > 0;
        }
        // open the list: focus the search box, or the chosen / first option
        function open() {
            panel.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            var selected = list.querySelector('[aria-selected="true"]');
            if (search) {
                search.value = '';
                filter();
                search.focus();
            } else {
                (selected || items[0]).focus();
            }
            if (selected) {
                selected.scrollIntoView({ block: 'nearest' });
            }
        }
        function close() {
            panel.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        }
        // pick an option: store its value, show its text, close the list
        function choose(li) {
            hidden.value = li.getAttribute('data-value');
            text.textContent = li.textContent;
            text.classList.remove('placeholder');
            items.forEach(function (i) {
                i.removeAttribute('aria-selected');
            });
            li.setAttribute('aria-selected', 'true');
            hidden.dispatchEvent(new Event('change'));
            close();
            btn.focus();
        }

        btn.addEventListener('click', function () {
            if (panel.hidden) {
                open();
            } else {
                close();
            }
        });

        // search box: English letters only; Enter picks the first match, ↓ goes into the list
        if (search) {
            search.addEventListener('input', function () {
                search.value = search.value.replace(/[^\x20-\x7E]/g, '');     // English letters only
                filter();
            });
            search.addEventListener('keydown', function (ev) {
                var shown = visible();
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    if (shown[0]) {
                        choose(shown[0]);
                    }
                }
                if (ev.key === 'ArrowDown' && shown[0]) {
                    ev.preventDefault();
                    shown[0].focus();
                }
                if (ev.key === 'Escape') {
                    close();
                    btn.focus();
                }
            });
        }

        // the list: click or Enter / Space picks; ↑ ↓ move; Esc closes
        list.addEventListener('click', function (ev) {
            var li = ev.target.closest('[data-value]');
            if (li) {
                choose(li);
            }
        });
        list.addEventListener('keydown', function (ev) {
            var shown = visible();
            var pos = shown.indexOf(document.activeElement);
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                if (pos > -1) {
                    choose(shown[pos]);
                }
            }
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                if (shown[pos + 1]) {
                    shown[pos + 1].focus();
                }
            }
            if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                if (pos > 0) {
                    shown[pos - 1].focus();
                } else if (search) {
                    search.focus();
                }
            }
            if (ev.key === 'Escape') {
                close();
                btn.focus();
            }
        });

        // a click outside closes the list
        document.addEventListener('click', function (ev) {
            if (!combo.contains(ev.target)) {
                close();
            }
        });
    });
})();
