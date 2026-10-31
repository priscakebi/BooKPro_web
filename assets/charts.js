// ======================================================
// INFOBULLES DES GRAPHIQUES (survol + clavier)
// Lit data-tip-value / data-tip-label / data-tip-detail.
// Textes insérés avec textContent : jamais interprétés comme HTML.
// ======================================================

(() => {

    const tip = document.createElement("div");
    tip.className = "viz-tip";
    tip.hidden = true;
    tip.setAttribute("role", "tooltip");
    document.body.appendChild(tip);

    function afficher(el, x, y) {
        tip.replaceChildren();

        const valeur = document.createElement("strong");
        valeur.textContent = el.dataset.tipValue;

        const libelle = document.createElement("span");
        libelle.textContent = el.dataset.tipLabel;

        tip.append(valeur, libelle);

        if (el.dataset.tipDetail) {
            const detail = document.createElement("span");
            detail.className = "d-block";
            detail.textContent = el.dataset.tipDetail;
            tip.append(detail);
        }

        tip.hidden = false;
        tip.style.left = Math.min(Math.max(x, 130), window.innerWidth - 130) + "px";
        tip.style.top = y + "px";
    }

    document.querySelectorAll("[data-tip-value]").forEach((el) => {

        el.addEventListener("pointermove", (e) => afficher(el, e.clientX, e.clientY));
        el.addEventListener("pointerleave", () => tip.hidden = true);

        // Clavier : même information au focus
        el.addEventListener("focus", () => {
            const r = el.getBoundingClientRect();
            afficher(el, r.left + r.width / 2, r.top);
        });
        el.addEventListener("blur", () => tip.hidden = true);
    });

})();
