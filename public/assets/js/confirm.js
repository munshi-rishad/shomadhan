// assets/js/confirm.js - our own "Are you sure?" box (instead of the browser's confirm()), same look on every page.
// Put on a <form>:
//   data-confirm="text"        question shown in the box
//   data-confirm-title="text"  heading (default: "Are you sure?")
//   data-confirm-changes       list what changed: every field with data-label, old value -> new value
//                              (nothing changed = nothing to save, the box only says so)
//   data-confirm-summary       list every field with data-label and its value (new account)
//   data-confirm-pw            the admin must type their own password; it is sent as "my_password" (the server checks it)
//   data-confirm-danger        red OK button (delete)
// The box itself is partials/confirm-modal.php (texts in the chosen language).
(function () {
    var box = document.getElementById('cm');
    if (!box) { return; }
    var $ = function (id) { return document.getElementById(id); };
    var title = $('cm-title'), msg = $('cm-msg'), list = $('cm-list'), pwWrap = $('cm-pw-wrap'),
        pw = $('cm-pw'), pwErr = $('cm-pw-err'), ok = $('cm-ok'), no = $('cm-no');
    var form = null, by = null, lastFocus = null;

    // what a field shows now / showed when the page opened
    function text(el, old) {
        if (el.type === 'checkbox') { return (old ? el.defaultChecked : el.checked) ? box.dataset.yes : box.dataset.no; }
        if (el.tagName === 'SELECT') {
            var o = [].filter.call(el.options, function (x) { return old ? x.defaultSelected : x.selected; })[0];
            if (old && !o) { o = el.options[0]; }
            return o ? o.text.trim() : '';
        }
        if (el.type === 'file') { return el.files && el.files.length ? el.files[0].name : ''; }
        return (old ? el.defaultValue : el.value).trim();
    }

    function row(label, from, to) {
        var li = document.createElement('li');
        var b = document.createElement('span');
        b.textContent = label + ': ';
        li.appendChild(b);
        if (from !== null) {
            var s = document.createElement('s');
            s.textContent = from === '' ? '—' : from;
            li.appendChild(s);
            li.appendChild(document.createTextNode(' → '));
        }
        var n = document.createElement('b');
        n.textContent = to === '' ? '—' : to;
        li.appendChild(n);
        list.appendChild(li);
    }

    function open(f, submitter) {
        form = f; by = submitter || null;
        lastFocus = document.activeElement;
        list.innerHTML = '';
        title.textContent = f.getAttribute('data-confirm-title') || box.dataset.sure;
        msg.textContent = f.getAttribute('data-confirm') || '';
        var changed = 0;
        var fields = [].slice.call(f.querySelectorAll('[data-label]')).filter(function (el) { return !el.disabled; });
        if (f.hasAttribute('data-confirm-changes')) {
            fields.forEach(function (el) {
                var from = text(el, true), to = text(el, false);
                if (el.type === 'file') {
                    if (to !== '') { row(el.getAttribute('data-label'), null, box.dataset.newPhoto); changed++; }
                } else if (from !== to) { row(el.getAttribute('data-label'), from, to); changed++; }
            });
            if (!changed) { msg.textContent = box.dataset.none; }
        } else if (f.hasAttribute('data-confirm-summary')) {
            fields.forEach(function (el) {
                var v = text(el, false);
                row(el.getAttribute('data-label'), null, el.type === 'file' && v !== '' ? box.dataset.newPhoto : v);
            });
            changed = 1;
        } else { changed = 1; }
        list.hidden = !list.children.length;
        var needPw = f.hasAttribute('data-confirm-pw') && changed > 0;
        pwWrap.hidden = !needPw;
        pw.value = '';
        pwErr.hidden = true;
        ok.hidden = changed === 0;
        ok.className = 'btn' + (f.hasAttribute('data-confirm-danger') ? ' btn-danger' : '');
        no.textContent = changed ? box.dataset.cancel : box.dataset.close;
        box.hidden = false;
        document.body.classList.add('cm-on');
        (needPw ? pw : (changed ? ok : no)).focus();
    }

    function close() {
        box.hidden = true;
        document.body.classList.remove('cm-on');
        form = null;
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
    }

    function go() {
        if (!form) { return; }
        if (!pwWrap.hidden) {
            if (pw.value === '') { pwErr.hidden = false; pw.focus(); return; }
            var h = form.querySelector('input[name="my_password"]');
            if (!h) { h = document.createElement('input'); h.type = 'hidden'; h.name = 'my_password'; form.appendChild(h); }
            h.value = pw.value;
        }
        var f = form, s = by;
        f._confirmed = true;
        close();
        if (f.requestSubmit) { f.requestSubmit(s && s.form === f ? s : undefined); } else { f.submit(); }
        f._confirmed = false;
    }

    document.addEventListener('submit', function (ev) {
        var f = ev.target;
        if (ev.defaultPrevented || f._confirmed) { return; }
        if (!f.hasAttribute('data-confirm') && !f.hasAttribute('data-confirm-changes') && !f.hasAttribute('data-confirm-summary')) { return; }
        if (ev.submitter && ev.submitter.hasAttribute('data-no-confirm')) { return; }
        ev.preventDefault();
        open(f, ev.submitter);
    });
    ok.addEventListener('click', go);
    no.addEventListener('click', close);
    pw.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); go(); } });
    box.addEventListener('click', function (ev) { if (ev.target === box) { close(); } });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !box.hidden) { close(); } });
})();
