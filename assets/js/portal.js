document.addEventListener("DOMContentLoaded", function () {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
});
/* Portal UX safeguards: stop double submits, warn before losing a half-written SMS. */
document.addEventListener("DOMContentLoaded", function () {
    var dirty = false;
    var sending = false;
    var composer = document.getElementById("sms-send");
    if (composer) {
        composer.addEventListener("input", function (event) {
            var name = event.target && event.target.name;
            if (name === "numbers" || name === "message_content") {
                dirty = true;
            }
        });
    }
    document.querySelectorAll("form[method='POST'], form[method='post']").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (form.id === "sms-send") {
                // The SMS composer has its own guard (Alpine) and must keep its button enabled.
                dirty = false;
                return;
            }
            if (form.dataset.submitted === "1") {
                event.preventDefault();
                return;
            }
            form.dataset.submitted = "1";
            dirty = false;
            sending = true;
            // Disable after the browser has read the clicked button's name/value.
            setTimeout(function () {
                form.querySelectorAll("button[type='submit'], input[type='submit']").forEach(function (button) {
                    button.disabled = true;
                    button.classList.add("is-busy");
                });
            }, 0);
            // Allow a retry only if the page is still here after a long wait (a stalled network).
            // SMS, KYC and billing requests can take well over 15 seconds, so a shorter wait
            // lets a second click send the same request twice.
            setTimeout(function () {
                form.dataset.submitted = "0";
                sending = false;
                form.querySelectorAll("button.is-busy").forEach(function (button) {
                    button.disabled = false;
                    button.classList.remove("is-busy");
                });
            }, 90000);
        });
    });
    window.addEventListener("beforeunload", function (event) {
        if (dirty && !sending) {
            event.preventDefault();
            event.returnValue = "";
        }
    });
});
