// validation.js - login + register forms (browser side).
// Same rules as AuthController.php (the server checks again).
//  - every box takes ENGLISH keyboard characters only (Bangla letters are removed)
//  - name = CAPITAL letters, email = small letters, mobile = digits only (max 11)
(function () {
    var form = document.querySelector('.form');
    if (!form) { return; }

    var msg = {};
    try { msg = JSON.parse(form.getAttribute('data-msg') || '{}'); } catch (e) { }
    function f(n) { return form.querySelector('[name="' + n + '"]'); }
    var name = f('name'), mobile = f('mobile'), email = f('email'), pass = f('password'),
        area = f('area'), gender = f('gender'), terms = f('terms');

    // ---- clean what the person types (keeps the cursor place)
    function clean(el, fn) {
        if (!el) { return; }
        el.addEventListener('input', function () {
            var before = el.value, after = fn(before);
            if (after === before) { return; }
            var pos = el.selectionStart;
            el.value = after;
            if (pos !== null) {
                try { var p = Math.max(0, pos - (before.length - after.length)); el.setSelectionRange(p, p); } catch (e) { }
            }
        });
    }
    clean(name, function (v) { return v.replace(/[^\x20-\x7E]/g, '').toUpperCase().replace(/[^A-Z .'-]/g, ''); });
    clean(mobile, function (v) { return v.replace(/\D/g, '').slice(0, 11); });
    clean(email, function (v) { return v.replace(/[^\x21-\x7E]/g, '').toLowerCase(); });
    clean(pass, function (v) { return v.replace(/[^\x21-\x7E]/g, ''); });

    // ---- eye button
    var toggle = form.querySelector('.toggle');
    toggle.addEventListener('click', function () {
        var show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
    });

    // ---- rules (return '' when OK)
    function nameMsg() {
        var v = name.value.trim().replace(/\s+/g, ' ');
        if (v === '') { return msg.name_empty; }
        return /^[A-Za-z][A-Za-z .'-]{1,49}$/.test(v) ? '' : msg.name_format;
    }
    function mobileMsg() {
        var v = mobile.value.trim();
        if (v === '') { return msg.mobile_empty; }
        if (!/^\d{11}$/.test(v)) { return msg.mobile_length; }
        return /^01[3-9]\d{8}$/.test(v) ? '' : msg.mobile_format;
    }
    function emailMsg() {                       // optional, but only Gmail
        var v = email.value.trim().toLowerCase();
        if (v === '') { return ''; }
        return /^[a-z0-9](?:[a-z0-9]|\.(?!\.)){4,28}[a-z0-9]@gmail\.com$/.test(v) ? '' : msg.email;
    }
    function passMsg() {
        var v = pass.value;
        if (v === '') { return msg.password_empty; }
        if (!/^[\x21-\x7E]+$/.test(v)) { return msg.password_ascii; }
        if (v.length < 8) { return msg.password_min; }
        return v.length > 128 ? msg.password_max : '';
    }
    function areaMsg() { return area.value === '' ? msg.area : ''; }
    function genderMsg() { return gender.value === '' ? msg.gender : ''; }
    function termsMsg() { return terms.checked ? '' : msg.terms; }

    function comboBtn(hidden) { return hidden ? hidden.closest('.combo').querySelector('.combo-btn') : null; }

    var fields = [];
    function add(el, id, fn, box) {
        if (el) { fields.push({ el: el, id: id, fn: fn, box: box === undefined ? el.closest('.input') : box }); }
    }
    add(name, 'err-name', nameMsg);
    add(mobile, 'err-mobile', mobileMsg);
    add(email, 'err-email', emailMsg);
    add(pass, 'err-password', passMsg);
    add(area, 'err-area', areaMsg, comboBtn(area));
    add(gender, 'err-gender', genderMsg, comboBtn(gender));
    add(terms, 'err-terms', termsMsg, null);

    function show(item) {
        var m = item.fn(), t = document.getElementById(item.id);
        if (item.box) { item.box.classList.toggle('invalid', !!m); }
        t.textContent = m || '';
        t.hidden = !m;
        return !m;
    }
    fields.forEach(function (item) {            // fix the error live while typing
        ['input', 'change'].forEach(function (ev) {
            item.el.addEventListener(ev, function () {
                if (!document.getElementById(item.id).hidden) { show(item); }
            });
        });
    });
    form.addEventListener('submit', function (ev) {
        var first = null;
        fields.forEach(function (item) { if (!show(item) && !first) { first = item; } });
        if (first) {
            ev.preventDefault();
            (first.el.type === 'hidden' ? comboBtn(first.el) : first.el).focus();
        }
    });

    // ---- dropdowns (area has a search box, gender does not)
    [].slice.call(form.querySelectorAll('.combo')).forEach(function (combo) {
        var hidden = combo.querySelector('input[type="hidden"]'),
            btn = combo.querySelector('.combo-btn'), panel = combo.querySelector('.combo-panel'),
            search = combo.querySelector('.combo-search'), list = combo.querySelector('.combo-list'),
            items = [].slice.call(list.querySelectorAll('[data-value]')), none = list.querySelector('.combo-empty'),
            text = combo.querySelector('.combo-text');

        function visible() { return items.filter(function (li) { return !li.hidden; }); }
        function filter() {
            if (!search) { return; }
            var q = search.value.trim().toLowerCase();
            items.forEach(function (li) { li.hidden = li.textContent.toLowerCase().indexOf(q) === -1; });
            none.hidden = visible().length > 0;
        }
        function open() {
            panel.hidden = false; btn.setAttribute('aria-expanded', 'true');
            var sel = list.querySelector('[aria-selected="true"]');
            if (search) { search.value = ''; filter(); search.focus(); } else { (sel || items[0]).focus(); }
            if (sel) { sel.scrollIntoView({ block: 'nearest' }); }
        }
        function close() { panel.hidden = true; btn.setAttribute('aria-expanded', 'false'); }
        function choose(li) {
            hidden.value = li.getAttribute('data-value');
            text.textContent = li.textContent; text.classList.remove('placeholder');
            items.forEach(function (i) { i.removeAttribute('aria-selected'); });
            li.setAttribute('aria-selected', 'true');
            hidden.dispatchEvent(new Event('change'));
            close(); btn.focus();
        }
        btn.addEventListener('click', function () { if (panel.hidden) { open(); } else { close(); } });
        if (search) {
            search.addEventListener('input', function () {
                search.value = search.value.replace(/[^\x20-\x7E]/g, '');     // English letters only
                filter();
            });
            search.addEventListener('keydown', function (ev) {
                var v = visible();
                if (ev.key === 'Enter') { ev.preventDefault(); if (v[0]) { choose(v[0]); } }
                if (ev.key === 'ArrowDown' && v[0]) { ev.preventDefault(); v[0].focus(); }
                if (ev.key === 'Escape') { close(); btn.focus(); }
            });
        }
        list.addEventListener('click', function (ev) {
            var li = ev.target.closest('[data-value]'); if (li) { choose(li); }
        });
        list.addEventListener('keydown', function (ev) {
            var v = visible(), i = v.indexOf(document.activeElement);
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); if (i > -1) { choose(v[i]); } }
            if (ev.key === 'ArrowDown') { ev.preventDefault(); if (v[i + 1]) { v[i + 1].focus(); } }
            if (ev.key === 'ArrowUp') { ev.preventDefault(); if (i > 0) { v[i - 1].focus(); } else if (search) { search.focus(); } }
            if (ev.key === 'Escape') { close(); btn.focus(); }
        });
        document.addEventListener('click', function (ev) { if (!combo.contains(ev.target)) { close(); } });
    });
})();
