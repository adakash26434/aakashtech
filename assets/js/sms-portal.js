function smsNepalDigits(part) {
    var digits = String(part || '').replace(/\D/g, '');
    if (digits.length === 13 && digits.slice(0, 3) === '977') digits = digits.slice(3);
    if (digits.length === 11 && digits.charAt(0) === '0') digits = digits.slice(1);
    return /^9[78]\d{8}$/.test(digits) ? digits : '';
}
function smsFillName(text, person) {
    return String(text).split('{name}').join(person || '').replace(/\s+,/g, ',').replace(/^[\s,]+/, '').replace(/ {2,}/g, ' ').trim();
}
function smsHasNames(numbers) {
    return String(numbers || '').split(/\r?\n/).some(function (line) {
        return String(line).split(/[\s,;]+/).some(function (part) {
            return part !== '' && !/\d/.test(part);
        });
    });
}
function smsComposer(seed) {
    var memo = { key: null, value: null };
    return {
        text: seed.text || '',
        numbers: seed.numbers || '',
        balance: seed.balance || 0,
        when: seed.when || '',
        sending: false,
        reviewing: false,
        importing: false,
        importNote: '',
        importOk: false,
        uploadOpen: false,
        dragging: false,
        keepTyped: true,
        reviewNote: '',
        nameFirst: function () {
            return String(this.text || '').indexOf('{name}') === 0;
        },
        setNameFirst: function (on) {
            var text = String(this.text || '');
            if (on && text.indexOf('{name}') === -1) {
                this.text = '{name}, ' + text.replace(/^\s+/, '');
            } else if (!on && text.indexOf('{name}') === 0) {
                this.text = text.replace(/^\{name\}[\s,]*/, '');
            }
            this.reviewing = false;
        },
        importDropped: function (event) {
            var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (file) this.sendFile(file);
        },
        addNumber: function (input) {
            var raw = String(input.value || '').trim();
            if (!raw) return;
            var digits = smsNepalDigits(raw);
            if (!digits) {
                this.importNote = 'Use a 10-digit Nepal mobile, such as 9800000001.';
                return;
            }
            var lines = String(this.numbers || '').split(/\r?\n/);
            var already = false;
            lines.forEach(function (line) {
                if (String(line).indexOf(digits) !== -1) already = true;
            });
            if (already) {
                this.importNote = digits + ' is already in the list.';
                input.value = '';
                return;
            }
            var existing = String(this.numbers || '').trim();
            this.numbers = existing ? existing + '\n' + digits : digits;
            this.importNote = digits + ' added.';
            this.reviewing = false;
            input.value = '';
        },
        openReview: function () {
            var form = document.getElementById('sms-send');
            if (form && !form.reportValidity()) return;
            var estimate = this.estimate();
            if (!estimate.count) {
                this.reviewNote = 'Add at least one Nepal mobile.';
                return;
            }
            if (estimate.brackets) {
                this.reviewNote = 'Replace the words in brackets first.';
                return;
            }
            if (estimate.short) {
                this.reviewNote = 'There are not enough credits for this send.';
                return;
            }
            this.reviewNote = '';
            this.reviewing = true;
            this.$nextTick(function () {
                var box = document.querySelector('.sms-review');
                if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },
        templates: seed.templates || [],
        lists: seed.lists || [],
        importFile: function (event) {
            var file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) this.sendFile(file);
        },
        sendFile: function (file) {
            var lower = String(file.name || '').toLowerCase();
            this.importOk = false;
            if (/\.xls$/.test(lower)) {
                this.importNote = 'Open the file in Excel and save it as .xlsx or .csv, then upload again.';
                return;
            }
            if (!/\.(xlsx|csv|txt)$/.test(lower)) {
                this.importNote = 'Upload an Excel .xlsx file or a .csv file.';
                return;
            }
            var self = this;
            self.importing = true;
            self.importNote = 'Reading the file…';
            var data = new FormData();
            data.append('csrf_token', seed.csrf || '');
            data.append('sheet', file);
            fetch('sms-import.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (body) {
                    self.importing = false;
                    if (body && body.numbers) {
                        var existing = String(self.numbers || '').trim();
                        var added = body.count;
                        if (self.keepTyped && existing) {
                            var have = {};
                            existing.split(/\r?\n/).forEach(function (line) {
                                String(line).split(/[\s,;]+/).forEach(function (part) {
                                    var digits = smsNepalDigits(part);
                                    if (digits) have[digits] = true;
                                });
                            });
                            var fresh = String(body.numbers).split('\n').filter(function (line) {
                                var parts = String(line).split(/[\s,;]+/);
                                return !have[smsNepalDigits(parts[parts.length - 1])];
                            });
                            added = fresh.length;
                            self.numbers = fresh.length ? existing + '\n' + fresh.join('\n') : existing;
                        } else {
                            self.numbers = body.numbers;
                        }
                        self.importOk = true;
                        self.reviewing = false;
                        self.importNote = added + ' numbers added from ' + file.name + '.';
                        if (smsHasNames(body.numbers) && String(self.text || '').indexOf('{name}') === -1) {
                            self.setNameFirst(true);
                            self.importNote += ' Each SMS will start with that person\'s name.';
                        }
                        self.uploadOpen = false;
                    } else {
                        self.importNote = body && body.error ? body.error : 'That file could not be read.';
                    }
                })
                .catch(function () {
                    self.importing = false;
                    self.importNote = 'That file could not be read.';
                });
        },
        pickTemplate: function (id) {
            var found = this.templates.filter(function (item) { return String(item.id) === String(id); })[0];
            if (!found) return;
            var keepName = this.nameFirst() && String(found.text).indexOf('{name}') === -1;
            this.text = found.text;
            if (keepName) this.setNameFirst(true);
        },
        pickList: function (id) {
            var found = this.lists.filter(function (item) { return String(item.id) === String(id); })[0];
            if (found) this.numbers = found.numbers;
        },
        estimate: function () {
            var key = String(this.text || '') + '\u0001' + String(this.numbers || '') + '\u0001' + this.balance;
            if (memo.key !== key) {
                memo.value = this.measure();
                memo.key = key;
            }
            return memo.value;
        },
        measure: function () {
            var text = String(this.text || '');
            var chars = Array.from(text);
            var unicode = /[^\n\r\x20-\x7E]/.test(text);
            var parts = 0;
            if (chars.length) {
                parts = unicode ? (chars.length <= 70 ? 1 : Math.ceil(chars.length / 67)) : (chars.length <= 160 ? 1 : Math.ceil(chars.length / 153));
            }
            var seen = {};
            var count = 0;
            var bad = 0;
            var credits = 0;
            var previewName = '';
            var usesName = text.indexOf('{name}') !== -1;
            var creditParts = function (body) {
                var bodyChars = Array.from(body);
                if (!bodyChars.length) return 0;
                var bodyUnicode = /[^\n\r\x20-\x7E]/.test(body);
                return bodyUnicode ? (bodyChars.length <= 70 ? 1 : Math.ceil(bodyChars.length / 67)) : (bodyChars.length <= 160 ? 1 : Math.ceil(bodyChars.length / 153));
            };
            String(this.numbers || '').split(/\r?\n/).forEach(function (line) {
                var bits = String(line || '').trim().split(/[\s,;]+/);
                var nums = [];
                var words = [];
                bits.forEach(function (part) {
                    if (!part) return;
                    if (!/\d/.test(part)) {
                        words.push(part);
                        return;
                    }
                    var digits = smsNepalDigits(part);
                    if (!digits) {
                        bad += 1;
                        return;
                    }
                    if (!seen[digits]) {
                        seen[digits] = 1;
                        nums.push(digits);
                    }
                });
                var person = words.join(' ');
                if (!previewName && person) previewName = person;
                nums.forEach(function () {
                    count += 1;
                    var body = usesName ? smsFillName(text, person) : text;
                    credits += body ? creditParts(body) : parts;
                });
            });
            if (!usesName) {
                credits = parts * count;
            }
            var language = unicode ? 'Nepali' : 'English';
            var label = chars.length
                ? language + ' · ' + chars.length + ' characters · ' + parts + ' credit' + (parts === 1 ? '' : 's') + ' per number'
                : 'English up to 160 characters is 1 SMS. Nepali up to 70 characters is 1 SMS.';
            if (count) {
                label += ' · ' + count + ' number' + (count === 1 ? '' : 's') + ' · ' + credits + ' credit' + (credits === 1 ? '' : 's');
            }
            if (bad) {
                label += ' · ' + bad + ' line' + (bad === 1 ? '' : 's') + ' not a Nepal mobile';
            }
            if (credits > this.balance) {
                label += ' · not enough credits (' + this.balance + ' left)';
            }
            var brackets = /\[[^\]\r\n]{1,40}\]/.test(text);
            if (brackets) {
                label += ' · replace the words in brackets';
            }
            var preview = usesName ? smsFillName(text, previewName) : text;
            var previewChars = Array.from(preview);
            if (previewChars.length > 180) {
                preview = previewChars.slice(0, 180).join('') + '…';
            }
            return { parts: parts, count: count, credits: credits, short: (credits > this.balance && credits > 0) || brackets, label: label, preview: preview, brackets: brackets };
        }
    };
}
