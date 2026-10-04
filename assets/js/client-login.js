            (function () {
                var form = document.getElementById('register-form');
                if (!form) return;
                var token = form.querySelector('input[name="csrf_token"]').value;
                var password = document.getElementById('reg-password');
                var confirm = document.getElementById('reg-confirm');
                function hint(id, message, ok) {
                    var node = document.getElementById(id);
                    if (!node) return;
                    node.textContent = message || '';
                    node.className = 'field-hint' + (message ? (ok ? ' field-hint--ok' : ' field-hint--bad') : '');
                }
                function passwords() {
                    var passHint = '';
                    if (password.value !== '' && password.value.length < 8) {
                        passHint = 'Password must be at least 8 characters.';
                    }
                    hint('reg-password-hint', passHint, false);
                    if (confirm.value === '') {
                        hint('reg-confirm-hint', '', false);
                        return passHint === '';
                    }
                    var same = password.value === confirm.value;
                    hint('reg-confirm-hint', same ? 'Passwords match.' : 'Passwords do not match.', same);
                    return passHint === '' && same;
                }
                function ask(field, input, hintId) {
                    var value = input.value.trim();
                    if (value === '') {
                        hint(hintId, '', false);
                        return;
                    }
                    var body = new FormData();
                    body.append('csrf_token', token);
                    body.append('check_account', '1');
                    body.append('field', field);
                    body.append('value', value);
                    fetch('login.php?action=register', { method: 'POST', body: body, credentials: 'same-origin' })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            hint(hintId, data && data.message ? data.message : '', !!(data && data.ok));
                        })
                        .catch(function () {});
                }
                var timers = {};
                function later(field, input, hintId) {
                    clearTimeout(timers[field]);
                    timers[field] = setTimeout(function () { ask(field, input, hintId); }, 400);
                }
                password.addEventListener('input', passwords);
                confirm.addEventListener('input', passwords);
                document.getElementById('reg-email').addEventListener('input', function () { later('email', this, 'reg-email-hint'); });
                document.getElementById('reg-phone').addEventListener('input', function () { later('phone', this, 'reg-phone-hint'); });
                document.getElementById('reg-company').addEventListener('input', function () { later('company', this, 'reg-company-hint'); });
                form.addEventListener('submit', function (event) {
                    if (!passwords()) {
                        event.preventDefault();
                        confirm.focus();
                    }
                    ['reg-email-hint', 'reg-phone-hint', 'reg-company-hint'].forEach(function (id) {
                        var node = document.getElementById(id);
                        if (node && node.className.indexOf('field-hint--bad') !== -1) {
                            event.preventDefault();
                        }
                    });
                });
            }());
