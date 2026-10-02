document.addEventListener("DOMContentLoaded", function () {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }

    var revealItems = document.querySelectorAll(".reveal");
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (!reduceMotion) {
        revealItems.forEach(function (item) {
            var parent = item.parentElement;
            if (!parent) return;
            var order = 0;
            var child = parent.firstElementChild;
            while (child) {
                if (child.classList && child.classList.contains("reveal")) {
                    if (child === item) break;
                    order += 1;
                }
                child = child.nextElementSibling;
            }
            item.style.setProperty("--reveal-delay", Math.min(order, 6) * 70 + "ms");
        });
    }

    if (reduceMotion || !("IntersectionObserver" in window)) {
        revealItems.forEach(function (item) {
            item.classList.add("revealed");
        });
    } else {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("revealed");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.14, rootMargin: "0px 0px -24px 0px" });

        revealItems.forEach(function (item) {
            observer.observe(item);
        });
    }

    document.querySelectorAll(".bill-calc").forEach(function (box) {
        var input = box.querySelector("input");
        var result = box.querySelector("[data-bill-result]");
        var table = box.parentElement ? box.parentElement.querySelector(".slab-table") : null;
        var rates = [];
        var unitName = box.getAttribute("data-unit-name") || "items";
        if (!input || !result) return;
        try {
            rates = JSON.parse(box.getAttribute("data-rates") || "[]");
        } catch (error) {
            rates = [];
        }

        var money = function (amount) {
            var rounded = Math.round(amount * 100) / 100;
            var whole = Math.abs(rounded - Math.round(rounded)) < 0.001;
            var text = whole
                ? String(Math.round(rounded))
                : rounded.toFixed(2);
            var parts = text.split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return "NPR " + parts.join(".");
        };

        var render = function () {
            var qty = parseInt(input.value, 10);
            var match = null;
            var index;
            if (table) {
                table.querySelectorAll("tr[data-min]").forEach(function (row) {
                    row.classList.remove("slab-row--live");
                });
            }
            if (!qty || qty < 1) {
                result.textContent = "Type a quantity from the table to see the bill.";
                return;
            }
            for (index = 0; index < rates.length; index += 1) {
                if (qty >= rates[index].min && qty <= rates[index].max) {
                    match = rates[index];
                    break;
                }
            }
            if (!match) {
                result.textContent = "That quantity is outside the table, so it cannot be ordered.";
                return;
            }
            if (table) {
                var liveRow = table.querySelector('tr[data-min="' + match.min + '"][data-max="' + match.max + '"]');
                if (liveRow) liveRow.classList.add("slab-row--live");
            }
            var net = Math.round(match.unit * qty * 100) / 100;
            var vat = Math.round(net * 13) / 100;
            var total = Math.round((net + vat) * 100) / 100;
            result.textContent = qty.toLocaleString("en-US") + " " + unitName + " at " + money(match.unit) + " each: " + money(net) + " plus VAT " + money(vat) + ". The bill is " + money(total) + ".";
        };

        input.addEventListener("input", render);
        render();
    });

    document.querySelectorAll("[data-service]").forEach(function (link) {
        link.addEventListener("click", function () {
            var serviceSelect = document.getElementById("service");
            if (serviceSelect) {
                serviceSelect.value = link.getAttribute("data-service");
            }
        });
    });

    var notice = document.getElementById("site-notice");
    if (notice) {
        var noticeKey = "aakash-notice-" + (notice.getAttribute("data-notice-key") || "");
        var noticeCard = notice.querySelector(".site-notice-card");
        var closeNotice = function () {
            notice.hidden = true;
            try { sessionStorage.setItem(noticeKey, "1"); } catch (error) {}
        };
        var alreadyClosed = false;
        try { alreadyClosed = sessionStorage.getItem(noticeKey) === "1"; } catch (error) {}
        if (!alreadyClosed) {
            notice.hidden = false;
            var closeButton = notice.querySelector("[data-notice-close]");
            if (closeButton) closeButton.focus();
        }
        notice.addEventListener("click", function (event) {
            if (event.target === notice) closeNotice();
        });
        var dismiss = notice.querySelector("[data-notice-close]");
        if (dismiss) dismiss.addEventListener("click", closeNotice);
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !notice.hidden) closeNotice();
        });
        if (noticeCard) {
            noticeCard.addEventListener("click", function (event) {
                event.stopPropagation();
            });
        }
    }

    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener("click", function (event) {
            var targetId = link.getAttribute("href");
            if (!targetId || targetId === "#") return;

            var target = document.querySelector(targetId);
            if (!target) return;

            event.preventDefault();
            target.scrollIntoView({
                behavior: reduceMotion ? "auto" : "smooth",
                block: "start"
            });
        });
    });
});