/* Password form: a strength bar, an "are they the same" note, and show/hide. Advisory only; the server decides. */
(function () {
    var fresh = document.getElementById('new_pass');
    var again = document.getElementById('confirm_pass');
    if (!fresh) { return; }
    var bars = document.querySelectorAll('#pass-meter span');
    var note = document.getElementById('match-note');
    function score(p) {
        var s = 0;
        if (p.length >= 8) { s++; }
        if (p.length >= 12) { s++; }
        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) { s++; }
        if (/\d/.test(p)) { s++; }
        if (/[^A-Za-z0-9]/.test(p)) { s++; }
        return Math.min(4, s);
    }
    function paint() {
        var s = fresh.value ? score(fresh.value) : 0;
        Array.prototype.forEach.call(bars, function (b, i) { b.className = i < s ? 'is-on is-' + s : ''; });
        if (again && note) {
            if (!again.value) { note.textContent = ''; note.className = 'field-hint'; }
            else if (again.value === fresh.value) { note.textContent = 'Both match.'; note.className = 'field-hint pro-ok'; }
            else { note.textContent = 'These two are different.'; note.className = 'field-error'; }
        }
    }
    fresh.addEventListener('input', paint);
    if (again) { again.addEventListener('input', paint); }
    var show = document.getElementById('show-pass');
    if (show) {
        show.addEventListener('change', function () {
            ['current_pass', 'new_pass', 'confirm_pass'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) { el.type = show.checked ? 'text' : 'password'; }
            });
        });
    }
    var form = document.getElementById('password-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (again && again.value !== fresh.value) { e.preventDefault(); paint(); again.focus(); }
        });
    }
})();
