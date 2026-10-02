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