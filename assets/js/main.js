/* ====== Aakash Technologies — Main JS ====== */

document.addEventListener('DOMContentLoaded', function () {

    /* --- Reveal on Scroll --- */
    const revealElements = document.querySelectorAll('.reveal');

    const revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                const delay = entry.target.dataset.delay || 0;
                setTimeout(function () {
                    entry.target.classList.add('revealed');
                }, delay);
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(function (el) {
        revealObserver.observe(el);
    });

    /* --- Animated Counter for Stats --- */
    const statNumbers = document.querySelectorAll('.stat-number');

    const counterObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    statNumbers.forEach(function (el) {
        counterObserver.observe(el);
    });

    function animateCounter(element) {
        var target = parseInt(element.dataset.target, 10);
        var suffix = element.dataset.suffix || '';
        var format = element.dataset.format || '';
        var duration = 2000;
        var startTime = null;

        function formatNumber(num) {
            if (format === 'million' && num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return num.toLocaleString('en-US');
            }
            return num.toString();
        }

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            element.textContent = formatNumber(current) + suffix;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = formatNumber(target) + suffix;
            }
        }
        requestAnimationFrame(step);
    }

    /* --- Hero Particles --- */
    var particleContainer = document.getElementById('particles');
    if (particleContainer) {
        for (var i = 0; i < 30; i++) {
            var particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (10 + Math.random() * 10) + 's';
            particle.style.width = (2 + Math.random() * 3) + 'px';
            particle.style.height = particle.style.width;
            particle.style.opacity = (0.2 + Math.random() * 0.4).toString();
            particleContainer.appendChild(particle);
        }
    }

    /* --- Back to Top Button --- */
    var backToTop = document.getElementById('back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 600) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
    }

    /* --- Smooth Scroll for Anchor Links --- */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;
            var target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                var offset = 80;
                var targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

});
