/* Identity form: districts follow the province, "same address" switch, phone camera and upload with
   automatic resizing, a progress count, and a draft kept in this browser so a reload loses nothing. */
(function () {
    var form = document.getElementById('kyc-form');
    if (!form) { return; }
    var draftKey = 'kyc-draft-' + form.getAttribute('data-kind') + '-' + (form.getAttribute('data-client') || 'me');

    // Districts that belong to the chosen province
    function wireAddress(province, district) {
        function filter() {
            var p = province.value;
            Array.prototype.forEach.call(district.options, function (o) {
                if (!o.value) { return; }
                var show = !p || o.getAttribute('data-province') === p;
                o.hidden = !show;
                o.disabled = !show;
            });
            var chosen = district.options[district.selectedIndex];
            if (chosen && chosen.value && chosen.disabled) { district.value = ''; }
        }
        province.addEventListener('change', filter);
        district.addEventListener('change', function () {
            var o = district.options[district.selectedIndex];
            if (o && o.getAttribute('data-province') && !province.value) { province.value = o.getAttribute('data-province'); filter(); }
        });
        filter();
    }
    ['perm', 'temp'].forEach(function (prefix) {
        var p = document.getElementById('f-' + prefix + '_province');
        var d = document.getElementById('f-' + prefix + '_district');
        if (p && d) { wireAddress(p, d); }
    });
    var issue = document.getElementById('f-issue_district') || document.getElementById('f-contact_issue_district');
    if (issue) { Array.prototype.forEach.call(issue.options, function () {}); }

    // Same as permanent address
    var same = document.getElementById('temp-same');
    var tempBox = form.querySelector('.kyc-temp');
    function applySame() {
        if (!same || !tempBox) { return; }
        var on = same.checked;
        tempBox.hidden = on;
        Array.prototype.forEach.call(tempBox.querySelectorAll('input, select, textarea'), function (el) { el.disabled = on; });
    }
    if (same) { same.addEventListener('change', applySame); applySame(); }

    // Placeholder for the date follows the calendar
    Array.prototype.forEach.call(form.querySelectorAll('.kyc-date'), function (box) {
        var input = box.querySelector('input');
        var cal = box.querySelector('select');
        cal.addEventListener('change', function () { input.placeholder = cal.value === 'BS' ? '2056-04-12' : '1999-07-28'; });
    });

    // Photos: shrink big phone pictures, show a preview
    function shrink(file, done) {
        if (!file || file.type.indexOf('image/') !== 0 || file.size < 1200000 || !window.createImageBitmap) { done(file); return; }
        createImageBitmap(file).then(function (bitmap) {
            var max = 2000;
            var scale = Math.min(1, max / Math.max(bitmap.width, bitmap.height));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(bitmap.width * scale);
            canvas.height = Math.round(bitmap.height * scale);
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            canvas.toBlob(function (blob) {
                if (!blob || blob.size >= file.size) { done(file); return; }
                try { done(new File([blob], (file.name || 'photo').replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' })); } catch (e) { done(file); }
            }, 'image/jpeg', 0.85);
        }).catch(function () { done(file); });
    }
    function size(n) { return n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
    Array.prototype.forEach.call(form.querySelectorAll('.kyc-doc'), function (box) {
        var inputs = box.querySelectorAll('input[type=file]');
        var status = box.querySelector('[data-status]');
        var thumb = box.querySelector('[data-thumb]');
        Array.prototype.forEach.call(inputs, function (input) {
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) { return; }
                // only one of the two inputs is sent for a slot
                Array.prototype.forEach.call(inputs, function (other) { if (other !== input) { other.value = ''; } });
                status.textContent = 'Preparing…';
                shrink(file, function (ready) {
                    if (ready !== file && window.DataTransfer) {
                        var dt = new DataTransfer();
                        dt.items.add(ready);
                        input.files = dt.files;
                    }
                    if (ready.size > 5242880) { status.textContent = 'This file is over 5 MB. Choose a smaller one.'; return; }
                    status.textContent = ready.name + ' · ' + size(ready.size);
                    box.classList.remove('has-error');
                    if (ready.type.indexOf('image/') === 0) {
                        var img = document.createElement('img');
                        img.alt = 'Preview';
                        img.src = URL.createObjectURL(ready);
                        thumb.innerHTML = '';
                        thumb.appendChild(img);
                    } else {
                        thumb.innerHTML = '<span class="kyc-thumb-pdf">PDF</span>';
                    }
                    update();
                });
            });
        });
        box.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ' ') && e.target.tagName === 'LABEL') { e.preventDefault(); e.target.querySelector('input').click(); }
        });
    });

    // Progress: how many of the required answers and documents are done
    var bar = document.getElementById('kyc-bar');
    var text = document.getElementById('kyc-progress-text');
    function update() {
        var need = 0, done = 0;
        Array.prototype.forEach.call(form.querySelectorAll('input[required], select[required], textarea[required]'), function (el) {
            if (el.disabled) { return; }
            need++;
            if (el.value.trim() !== '') { done++; }
        });
        Array.prototype.forEach.call(form.querySelectorAll('.kyc-doc'), function (box) {
            if (!box.querySelector('.req-mark')) { return; }
            need++;
            var got = box.querySelector('[data-thumb] img, [data-thumb] .kyc-thumb-pdf');
            var picked = Array.prototype.some.call(box.querySelectorAll('input[type=file]'), function (i) { return i.files && i.files.length; });
            if (got || picked) { done++; }
        });
        var pct = need ? Math.round(done * 100 / need) : 0;
        if (bar) { bar.style.width = pct + '%'; }
        if (text) { text.textContent = done + ' of ' + need + ' required items done'; }
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);

    // Draft kept in this browser tab only, never on disk after the tab closes (text only; photos are never stored)
    function save() {
        try {
            var data = {};
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || el.type === 'file' || el.type === 'hidden' || el.name === 'csrf_token') { return; }
                if (el.type === 'checkbox') { data[el.name] = el.checked ? '1' : ''; } else { data[el.name] = el.value; }
            });
            sessionStorage.setItem(draftKey, JSON.stringify(data));
        } catch (e) {}
    }
    function restore() {
        try {
            var raw = sessionStorage.getItem(draftKey);
            if (!raw || form.querySelector('.has-error')) { return; }
            var data = JSON.parse(raw);
            var empty = Array.prototype.every.call(form.querySelectorAll('input[type=text], input[type=tel], input[type=email], textarea'), function (el) { return el.value === ''; });
            if (!empty) { return; }
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.name || !(el.name in data) || el.type === 'file' || el.type === 'hidden') { return; }
                if (el.type === 'checkbox') { el.checked = data[el.name] === '1'; } else { el.value = data[el.name]; }
            });
            ['perm', 'temp'].forEach(function (prefix) {
                var p = document.getElementById('f-' + prefix + '_province');
                var d = document.getElementById('f-' + prefix + '_district');
                if (p && d) { var keep = d.value; p.dispatchEvent(new Event('change')); d.value = keep; }
            });
            applySame();
        } catch (e) {}
    }
    var timer = null;
    form.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(save, 600); });
    form.addEventListener('submit', function (e) {
        var bad = form.querySelector('input:invalid:not(:disabled), select:invalid:not(:disabled), textarea:invalid:not(:disabled)');
        if (bad) {
            e.preventDefault();
            bad.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bad.focus();
            return;
        }
        try { sessionStorage.removeItem(draftKey); } catch (x) {}
    });
    restore();
    update();
})();

/* Document viewer: opens a photo or PDF in a window with zoom and turn. Used on client and admin pages. */
(function () {
    var links = document.querySelectorAll('[data-kyc-doc]');
    if (!links.length) { return; }
    var box = document.createElement('div');
    box.className = 'kyc-viewer';
    box.hidden = true;
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.innerHTML = '<div class="kyc-viewer-bar"><strong></strong><span><button type="button" data-act="out" aria-label="Zoom out">−</button><button type="button" data-act="in" aria-label="Zoom in">+</button><button type="button" data-act="turn" aria-label="Turn">⟳</button><a data-act="open" target="_blank" rel="noopener">Open</a><button type="button" data-act="close" aria-label="Close">✕</button></span></div><div class="kyc-viewer-stage"></div>';
    document.body.appendChild(box);
    var stage = box.querySelector('.kyc-viewer-stage');
    var title = box.querySelector('strong');
    var open = box.querySelector('[data-act=open]');
    var zoom = 1, turn = 0, last = null;
    function paint() { var img = stage.querySelector('img'); if (img) { img.style.transform = 'rotate(' + turn + 'deg) scale(' + zoom + ')'; } }
    function show(a) {
        last = a; zoom = 1; turn = 0;
        title.textContent = a.getAttribute('data-title');
        open.href = a.getAttribute('data-src');
        stage.textContent = '';
        var node = document.createElement(a.getAttribute('data-type') === 'pdf' ? 'iframe' : 'img');
        if (node.tagName === 'IFRAME') { node.title = 'Document'; } else { node.alt = a.getAttribute('data-title') || ''; }
        node.src = a.getAttribute('data-src') || '';
        stage.appendChild(node);
        box.hidden = false;
        document.body.classList.add('kyc-lock');
        box.querySelector('[data-act=close]').focus();
    }
    function close() { box.hidden = true; stage.innerHTML = ''; document.body.classList.remove('kyc-lock'); if (last) { last.focus(); } }
    Array.prototype.forEach.call(links, function (a) { a.addEventListener('click', function (e) { e.preventDefault(); show(a); }); });
    box.addEventListener('click', function (e) {
        var act = e.target.getAttribute && e.target.getAttribute('data-act');
        if (act === 'close' || e.target === box) { close(); }
        if (act === 'in') { zoom = Math.min(4, zoom + 0.25); paint(); }
        if (act === 'out') { zoom = Math.max(0.5, zoom - 0.25); paint(); }
        if (act === 'turn') { turn = (turn + 90) % 360; paint(); }
    });
    document.addEventListener('keydown', function (e) { if (!box.hidden && e.key === 'Escape') { close(); } });
})();

/* Identity form as steps: one section at a time with tabs and Back / Next. Every field stays in the
   form, so nothing is lost. Next checks the current section first, and submit opens the first section
   that still needs an answer. */
(function () {
    var form = document.getElementById('kyc-form');
    if (!form) { return; }
    var sections = Array.prototype.slice.call(form.querySelectorAll('fieldset.kyc-section'));
    if (sections.length < 2) { return; }
    var current = 0;

    var bar = document.createElement('div');
    bar.className = 'kyc-tabs';
    bar.setAttribute('role', 'tablist');
    bar.setAttribute('aria-label', 'Form sections');
    sections.forEach(function (s, i) {
        var t = document.createElement('button');
        t.type = 'button';
        t.className = 'kyc-tab';
        t.setAttribute('role', 'tab');
        t.id = 'kyc-tab-' + i;
        var legend = s.querySelector('legend');
        t.textContent = (i + 1) + '. ' + (legend ? legend.textContent.replace(/^\s*\d+\s*/, '').trim() : 'Section ' + (i + 1));
        t.addEventListener('click', function () { show(i); });
        bar.appendChild(t);
    });
    sections[0].parentNode.insertBefore(bar, sections[0]);

    var nav = document.createElement('div');
    nav.className = 'kyc-stepnav';
    var back = document.createElement('button');
    back.type = 'button';
    back.className = 'btn btn-secondary';
    back.textContent = 'Back';
    back.addEventListener('click', function () { show(current - 1); });
    var next = document.createElement('button');
    next.type = 'button';
    next.className = 'btn btn-primary';
    next.textContent = 'Next';
    next.addEventListener('click', function () {
        if (!sectionValid(current)) { return; }
        show(current + 1);
    });
    nav.appendChild(back);
    nav.appendChild(next);
    sections[sections.length - 1].parentNode.insertBefore(nav, sections[sections.length - 1].nextSibling);

    function sectionValid(i) {
        var bad = null;
        Array.prototype.forEach.call(sections[i].querySelectorAll('input, select, textarea'), function (c) {
            if (!bad && !c.disabled && !c.checkValidity()) { bad = c; }
        });
        if (bad) {
            show(i);
            bad.reportValidity();
            bad.focus();
            return false;
        }
        return true;
    }

    function show(i) {
        if (i < 0 || i >= sections.length) { return; }
        current = i;
        sections.forEach(function (s, k) { s.hidden = k !== i; });
        Array.prototype.forEach.call(bar.children, function (t, k) {
            t.setAttribute('aria-selected', k === i ? 'true' : 'false');
            t.classList.toggle('is-on', k === i);
        });
        back.disabled = i === 0;
        next.hidden = i === sections.length - 1;
        sections[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // On submit, open the first section that still has a problem, so the browser can show its message.
    form.addEventListener('submit', function (e) {
        for (var i = 0; i < sections.length; i++) {
            var bad = sections[i].querySelector('input:invalid:not(:disabled), select:invalid:not(:disabled), textarea:invalid:not(:disabled)');
            if (bad) { show(i); return; }
        }
    }, true);

    show(0);
})();
