// ======================================================
// AFFICHER / MASQUER UN MOT DE PASSE
// ======================================================
function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    const icon = button.querySelector("i");

    const visible = input.type === "password";

    input.type = visible ? "text" : "password";

    icon.classList.toggle("bi-eye-fill", !visible);
    icon.classList.toggle("bi-eye-slash-fill", visible);
}

document.addEventListener("DOMContentLoaded", () => {

    // ==================================================
    // NAVBAR : fond vitré dès qu'on défile
    // ==================================================
    const nav = document.querySelector(".bp-nav");

    if (nav) {
        const onScroll = () => nav.classList.toggle("scrolled", window.scrollY > 30);
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
    }

    // ==================================================
    // APPARITION DES ÉLÉMENTS AU SCROLL
    // ==================================================
    const reveals = document.querySelectorAll(".reveal");

    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        reveals.forEach((el) => observer.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add("visible"));
    }

    // ==================================================
    // COMPTEURS ANIMÉS (data-count="123")
    // ==================================================
    document.querySelectorAll("[data-count]").forEach((el) => {
        const cible = parseInt(el.dataset.count, 10) || 0;
        const debut = performance.now();
        const duree = 1200;

        const tick = (now) => {
            const p = Math.min((now - debut) / duree, 1);
            el.textContent = Math.round(cible * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    });

    // ==================================================
    // SÉLECTEUR DE JOUR (créneaux)
    // ==================================================
    document.querySelectorAll("[data-day-tabs]").forEach((group) => {
        const scope = group.closest("[data-day-scope]") || document;

        group.querySelectorAll(".day-tab").forEach((tab) => {
            tab.addEventListener("click", () => {
                group.querySelectorAll(".day-tab").forEach((t) => t.classList.remove("active"));
                scope.querySelectorAll(".day-panel").forEach((p) => p.classList.remove("active"));
                tab.classList.add("active");
                scope.querySelector("#" + tab.dataset.target)?.classList.add("active");
            });
        });
    });

    // ==================================================
    // FILTRES : envoi automatique quand on change un select
    // ==================================================
    document.querySelectorAll("[data-autosubmit]").forEach((el) => {
        el.addEventListener("change", () => el.form.submit());
    });

    // ==================================================
    // SIDEBAR MOBILE
    // ==================================================
    const burgers = document.querySelectorAll(".app-burger, .app-burger-tab");
    const sidebar = document.querySelector(".app-sidebar");

    if (burgers.length && sidebar) {
        burgers.forEach((b) => b.addEventListener("click", () => sidebar.classList.toggle("open")));

        document.addEventListener("click", (e) => {
            if (!sidebar.contains(e.target) && ![...burgers].some((b) => b.contains(e.target))) {
                sidebar.classList.remove("open");
            }
        });
    }

    // ==================================================
    // APERÇU DE LA PHOTO AVANT ENVOI
    // ==================================================
    document.querySelectorAll("[data-photo-preview]").forEach((input) => {
        input.addEventListener("change", () => {
            const file = input.files[0];
            const target = document.querySelector(input.dataset.photoPreview);

            if (file && target) {
                const size = (target.dataset.size || 140) + "px";
                const img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                img.className = "avatar";
                img.style.width = img.style.height = size;
                target.replaceChildren(img);
            }
        });
    });
});
