/* Shared form behaviour for every page: friendly inline errors and sensible mobile keyboards. */
(function () {
    function fieldMessage(field) {
        var label = field.getAttribute("aria-label") || "";
        var v = field.validity;
        if (v.valueMissing) { return field.type === "checkbox" ? "Please tick this box to continue." : "This field is required."; }
        if (v.typeMismatch) { return field.type === "email" ? "Enter a valid email address, like name@example.com." : "Enter a valid value."; }
        if (v.patternMismatch) { return field.title || "Check the format and try again."; }
        if (v.tooShort) { return "Use at least " + field.minLength + " characters."; }
        if (v.tooLong) { return "Use at most " + field.maxLength + " characters."; }
        if (v.rangeUnderflow || v.rangeOverflow) { return "That number is out of range."; }
        return field.validationMessage || label;
    }
    function errorBox(field) {
        var id = (field.id || field.name || "f") + "-error";
        var box = document.getElementById(id);
        if (!box) {
            box = document.createElement("span");
            box.id = id;
            box.className = "field-error";
            box.setAttribute("role", "alert");
            field.insertAdjacentElement("afterend", box);
        }
        return box;
    }
    function clearError(field) {
        field.removeAttribute("aria-invalid");
        var id = (field.id || field.name || "f") + "-error";
        var box = document.getElementById(id);
        if (box) { box.remove(); }
        field.removeAttribute("aria-describedby");
    }
    document.addEventListener("invalid", function (event) {
        var field = event.target;
        if (!field || !field.form) { return; }
        event.preventDefault();
        var box = errorBox(field);
        box.textContent = fieldMessage(field);
        field.setAttribute("aria-invalid", "true");
        field.setAttribute("aria-describedby", box.id);
        if (!document.querySelector("[aria-invalid='true']:focus")) {
            var first = field.form.querySelector("[aria-invalid='true']");
            if (first === field) { field.focus({ preventScroll: false }); }
        }
    }, true);
    document.addEventListener("input", function (event) {
        var field = event.target;
        if (field && field.getAttribute && field.getAttribute("aria-invalid") === "true" && field.checkValidity()) { clearError(field); }
    });
    document.addEventListener("change", function (event) {
        var field = event.target;
        if (field && field.getAttribute && field.getAttribute("aria-invalid") === "true" && field.checkValidity()) { clearError(field); }
    });
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("input").forEach(function (input) {
            var name = (input.name || "").toLowerCase();
            if (input.type === "text" && !input.inputMode) {
                if (/phone|mobile|contact_no/.test(name)) { input.inputMode = "tel"; if (!input.autocomplete) { input.autocomplete = "tel"; } }
                else if (/otp|code|human_check|amount|quantity|qty/.test(name)) { input.inputMode = "numeric"; }
            }
            if (input.type === "email" && !input.autocomplete) { input.autocomplete = "email"; }
        });
    });
})();
