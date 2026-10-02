<?php
$askToken = function_exists('csrf_token') ? csrf_token() : '';
?>
<div class="ask-dock">
    <button class="ask-open" type="button" aria-expanded="false" aria-controls="ask-panel">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 4a7 7 0 0 0-7 7v1.2A3.8 3.8 0 0 0 8.8 16H9v2.2L12.4 16H13a7 7 0 0 0 0-14z"/><path d="M9 11h.01M12 11h.01M15 11h.01"/></svg>
        Ask about the services
    </button>
    <section id="ask-panel" class="ask-panel" hidden>
        <div class="ask-panel-head">
            <div>
                <strong>Ask about the services</strong>
                <p>Answers come from the public pages.</p>
            </div>
            <button class="ask-close" type="button" aria-label="Close">×</button>
        </div>
        <div class="ask-log" aria-live="polite"></div>
        <form class="ask-form">
            <input type="hidden" name="csrf_token" value="<?= site_escape($askToken) ?>">
            <label class="ask-label" for="ask-question">Your question</label>
            <textarea id="ask-question" name="question" rows="2" maxlength="600" required placeholder="For example, what does a .com.np name include?"></textarea>
            <button class="button button--primary" type="submit">Ask</button>
        </form>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dock = document.querySelector('.ask-dock');
    if (!dock) return;
    var openButton = dock.querySelector('.ask-open');
    var panel = dock.querySelector('.ask-panel');
    var closeButton = dock.querySelector('.ask-close');
    var form = dock.querySelector('.ask-form');
    var log = dock.querySelector('.ask-log');
    var field = dock.querySelector('#ask-question');
    var busy = false;

    function setOpen(open) {
        panel.hidden = !open;
        openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) field.focus();
    }

    function addLine(kind, text) {
        var line = document.createElement('p');
        line.className = 'ask-line ask-line--' + kind;
        line.textContent = text;
        log.appendChild(line);
        log.scrollTop = log.scrollHeight;
    }

    openButton.addEventListener('click', function () {
        setOpen(panel.hidden);
    });
    closeButton.addEventListener('click', function () {
        setOpen(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) setOpen(false);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (busy) return;
        var question = field.value.replace(/^\s+|\s+$/g, '');
        if (!question) return;
        busy = true;
        addLine('you', question);
        field.value = '';
        addLine('wait', 'Looking at the public pages...');
        var body = new FormData(form);
        body.set('question', question);
        fetch('ask.php', { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var waiting = log.querySelector('.ask-line--wait');
                if (waiting) waiting.parentNode.removeChild(waiting);
                if (data && data.ok && data.answer) {
                    addLine('ai', data.answer);
                } else {
                    addLine('ai', (data && data.error) ? data.error : 'The assistant could not answer just now.');
                }
            })
            .catch(function () {
                var waiting = log.querySelector('.ask-line--wait');
                if (waiting) waiting.parentNode.removeChild(waiting);
                addLine('ai', 'The assistant could not answer just now. Use the contact form or a support ticket.');
            })
            .then(function () { busy = false; });
    });
});
</script>
