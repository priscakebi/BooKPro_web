// ======================================================
// APPLICATION PRO : alertes en direct des nouvelles demandes
// Vérification toutes les 30 s (filet de sécurité si le push
// n'est pas disponible) + confirmation en un geste.
// S'appuie sur assets/pwa.js (window.bpToast, window.bpSw).
// ======================================================

(() => {

    const BASE = document.body.dataset.base || "";
    const CLE_VUES = "bookpro-demandes-vues";
    let premiereVerif = true;

    async function verifierDemandes() {
        let data;

        try {
            const r = await fetch(BASE + "/pro/api/attente.php", { credentials: "same-origin", cache: "no-store" });
            if (!r.ok) return;
            data = await r.json();
        } catch (e) {
            return; // hors ligne : on réessaiera
        }

        // Pastilles "Réservations" (menu latéral + barre d'onglets)
        document.querySelectorAll('a[href$="/pro/rdv.php"]').forEach((lien) => {
            const dansOnglets = !!lien.closest(".app-tabbar");
            const hote = dansOnglets ? lien.querySelector("span.position-relative") : lien;
            if (!hote) return;

            let pastille = hote.querySelector(dansOnglets ? ".tab-count" : ".menu-count");
            if (data.total > 0 && !pastille) {
                pastille = document.createElement("span");
                pastille.className = dansOnglets ? "tab-count" : "menu-count";
                hote.appendChild(pastille);
            }
            if (pastille) data.total > 0 ? pastille.textContent = data.total : pastille.remove();
        });

        const vues = new Set(JSON.parse(localStorage.getItem(CLE_VUES) || "[]"));
        const nouvelles = data.demandes.filter((d) => !vues.has(d.id));

        // Au premier chargement, on mémorise sans alerter (la page les affiche déjà)
        if (!premiereVerif) {
            nouvelles.forEach(afficherDemande);
        }

        premiereVerif = false;
        localStorage.setItem(CLE_VUES, JSON.stringify(data.demandes.map((d) => d.id)));
    }

    function afficherDemande(d) {
        window.bpToast?.(d.title, d.body, "demande", [
            { label: "Confirmer", style: "btn-success", onClick: (el) => agir(d, "confirmer", el) },
            { label: "Refuser", style: "btn-outline-danger", onClick: (el) => agir(d, "refuser", el) },
        ], d.url);

        // Application en arrière-plan sans push : notification système
        const sw = window.bpSw;
        if (document.hidden && Notification.permission === "granted" && sw) {
            sw.pushManager.getSubscription().then((abonne) => {
                if (!abonne) sw.showNotification(d.title, {
                    body: d.body, tag: d.tag, data: d, icon: BASE + "/assets/icons/icon-192.png",
                    actions: [{ action: "confirmer", title: "✓ Confirmer" }, { action: "refuser", title: "✕ Refuser" }],
                });
            });
        }
    }

    async function agir(d, action, toastEl) {
        toastEl.querySelectorAll("button").forEach((b) => b.disabled = true);

        const r = await fetch(BASE + "/pro/api/action.php", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: d.id, action, jeton: d.actions[action] }),
        });
        const res = await r.json().catch(() => ({}));

        toastEl.remove();
        window.bpToast?.(res.ok ? res.message : "Action impossible", res.ok ? "" : (res.message || ""), res.ok ? "success" : "danger");

        // Les pages qui listent les réservations se mettent à jour
        if (res.ok && /\/pro\/(agenda|rdv|pro-dashboard)\.php/.test(location.pathname)) {
            setTimeout(() => location.reload(), 900);
        } else {
            verifierDemandes();
        }
    }

    document.addEventListener("bp:maj", verifierDemandes);

    verifierDemandes();
    setInterval(verifierDemandes, 30000);
    document.addEventListener("visibilitychange", () => { if (!document.hidden) verifierDemandes(); });

})();
