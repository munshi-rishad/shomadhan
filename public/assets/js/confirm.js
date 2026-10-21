// assets/js/confirm.js - our own "Are you sure?" box (instead of the browser's confirm()), same look on every page.
// Put on a <form>:
//   data-confirm="text"        question shown in the box
//   data-confirm-title="text"  heading (default: "Are you sure?")
//   data-confirm-changes       list what changed: every field with data-label, old value -> new value
//                              (nothing changed = nothing to save, the box only says so)
//   data-confirm-summary       list every field with data-label and its value (new account)
//   data-confirm-pw            the admin must type their own password; it is sent as "my_password" (the server checks it)
//   data-confirm-danger        red OK button (delete)
// A submit button with data-no-confirm skips the box.
// The box itself is partials/confirm-modal.php (texts in the chosen language, in its data-* attributes).
(function () {
    var box = document.getElementById('cm');
    if (!box) {
        return;
    }
    var $ = function (id) {
        return document.getElementById(id);
    };
    var title = $('cm-title');
    var msg = $('cm-msg');
    var list = $('cm-list');
    var pwWrap = $('cm-pw-wrap');
    var pw = $('cm-pw');
    var pwErr = $('cm-pw-err');
    var ok = $('cm-ok');
    var no = $('cm-no');

    var form = null;        // the form waiting for an answer
    var submitter = null;   // the button that submitted it (sent again on OK)
    var lastFocus = null;   // focus goes back here when the box closes

    // ---- What a field shows now (old = false) or showed when the page opened (old = true) ----
    function text(el, old) {
        if (el.type === 'checkbox') {
            return (old ? el.defaultChecked : el.checked) ? box.dataset.yes : box.dataset.no;
        }
        if (el.tagName === 'SELECT') {
            var option = [].filter.call(el.options, function (x) {
                return old ? x.defaultSelected : x.selected;
            })[0];
            if (old && !option) {
                option = el.options[0];
            }
            return option ? option.text.trim() : '';
        }
        if (el.type === 'file') {
            return el.files && el.files.length ? el.files[0].name : '';
        }
        return (old ? el.defaultValue : el.value).trim();
    }

    // ---- Long texts: only the start is shown in the list ----
    function cut(value) {
        return value.length > 80 ? value.slice(0, 77) + '…' : value;
    }

    // ---- One line of the list: "Label: old -> new" (from = null: only "Label: new") ----
    function row(label, from, to) {
        from = from === null ? null : cut(from);
        to = cut(to);
        var li = document.createElement('li');
        var labelEl = document.createElement('span');
        labelEl.textContent = label + ': ';
        li.appendChild(labelEl);
        if (from !== null) {
            var oldEl = document.createElement('s');
            oldEl.textContent = from === '' ? '—' : from;
            li.appendChild(oldEl);
            li.appendChild(document.createTextNode(' → '));
        }
        var newEl = document.createElement('b');
        newEl.textContent = to === '' ? '—' : to;
        li.appendChild(newEl);
        list.appendChild(li);
    }

    // ---- Open the box for a form: fill the title, message, list and password field ----
    function open(f, by) {
        form = f;
        submitter = by || null;
        lastFocus = document.activeElement;
        list.innerHTML = '';
        title.textContent = f.getAttribute('data-confirm-title') || box.dataset.sure;
        msg.textContent = f.getAttribute('data-confirm') || '';
        var changed = 0;
        var fields = [].slice.call(f.querySelectorAll('[data-label]')).filter(function (el) {
            return !el.disabled;
        });
        if (f.hasAttribute('data-confirm-changes')) {
            // list only the fields that changed; a new photo is shown as "new photo"
            fields.forEach(function (el) {
                var from = text(el, true);
                var to = text(el, false);
                if (el.type === 'file') {
                    if (to !== '') {
                        row(el.getAttribute('data-label'), null, box.dataset.newPhoto);
                        changed++;
                    }
                } else if (from !== to) {
                    row(el.getAttribute('data-label'), from, to);
                    changed++;
                }
            });
            if (!changed) {
                msg.textContent = box.dataset.none;
            }
        } else if (f.hasAttribute('data-confirm-summary')) {
            // list every field with its value
            fields.forEach(function (el) {
                var value = text(el, false);
                row(el.getAttribute('data-label'), null, el.type === 'file' && value !== '' ? box.dataset.newPhoto : value);
            });
            changed = 1;
        } else {
            changed = 1;
        }
        list.hidden = !list.children.length;

        // nothing changed = no OK button and no password, only "close"
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

    // ---- Close the box without sending ----
    function close() {
        box.hidden = true;
        document.body.classList.remove('cm-on');
        form = null;
        if (lastFocus && lastFocus.focus) {
            lastFocus.focus();
        }
    }

    // ---- OK: copy the typed password into the form and send it for real ----
    function go() {
        if (!form) {
            return;
        }
        if (!pwWrap.hidden) {
            if (pw.value === '') {
                pwErr.hidden = false;
                pw.focus();
                return;
            }
            var pwInput = form.querySelector('input[name="my_password"]');
            if (!pwInput) {
                pwInput = document.createElement('input');
                pwInput.type = 'hidden';
                pwInput.name = 'my_password';
                form.appendChild(pwInput);
            }
            pwInput.value = pw.value;
        }
        var f = form;
        var by = submitter;
        f._confirmed = true;      // the submit listener below lets this one through
        close();
        if (f.requestSubmit) {
            f.requestSubmit(by && by.form === f ? by : undefined);
        } else {
            f.submit();
        }
        f._confirmed = false;
    }

    // ---- Catch every submit of a form that asks for a confirm ----
    document.addEventListener('submit', function (ev) {
        var f = ev.target;
        if (ev.defaultPrevented || f._confirmed) {
            return;
        }
        if (!f.hasAttribute('data-confirm') && !f.hasAttribute('data-confirm-changes') && !f.hasAttribute('data-confirm-summary')) {
            return;
        }
        if (ev.submitter && ev.submitter.hasAttribute('data-no-confirm')) {
            return;
        }
        ev.preventDefault();
        open(f, ev.submitter);
    });

    // ---- Buttons and keys: OK, Cancel, Enter in the password, click outside, Esc ----
    ok.addEventListener('click', go);
    no.addEventListener('click', close);
    pw.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            go();
        }
    });
    box.addEventListener('click', function (ev) {
        if (ev.target === box) {
            close();
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !box.hidden) {
            close();
        }
    });
})();
