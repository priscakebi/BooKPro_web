// ======================================================
// APPLICATIONS BOOKPRO (PWA) : socle commun pro + client
// - service worker de l'application (pro/ ou user/)
// - installation sur l'écran d'accueil
// - notifications push (activation / désactivation)
// - petits messages (toasts) : window.bpToast(titre, texte, type)
// ======================================================

(() => {

    const body = document.body;
    const BASE = body.dataset.base || "";
    const APP = body.dataset.app;               // "pro" ou "user"
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const VAPID = body.dataset.vapid || "";

    if (!APP) return;

    const pushDispo = "serviceWorker" in navigator && "PushManager" in window && "Notification" in window;
    const estInstallee = window.matchMedia("(display-mode: standalone)").matches || navigator.standalone;
    const estIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    let swReg = null;

    // ==================================================
    // SERVICE WORKER
    // ==================================================
    if ("serviceWorker" in navigator) {
        navigator.serviceWorker.register(BASE + "/" + APP + "/sw.js", { scope: BASE + "/" + APP + "/" })
            .then((reg) => { swReg = reg; window.bpSw = reg; majEtatPush(); })
            .catch((e) => console.warn("Service worker :", e));

        // Le service worker signale un changement (notification reçue, action faite)
        navigator.serviceWorker.addEventListener("message", (e) => {
            if (e.data?.type === "maj") document.dispatchEvent(new CustomEvent("bp:maj"));
        });
    }

    // ==================================================
    // INSTALLATION
    // ==================================================
    let invite = null;

    window.addEventListener("beforeinstallprompt", (e) => {
        e.preventDefault();
        invite = e;
        document.querySelectorAll("[data-install-app]").forEach((b) => b.classList.remove("d-none"));
    });

    document.querySelectorAll("[data-install-app]").forEach((btn) => {
        // iOS : pas d'invite automatique, on explique la marche à suivre
        if (estIOS && !estInstallee) btn.classList.remove("d-none");

        btn.addEventListener("click", async () => {
            if (invite) {
                invite.prompt();
                await invite.userChoice;
                invite = null;
                btn.classList.add("d-none");
            } else if (estIOS) {
                toast("Installer sur iPhone", "Touchez <strong>Partager</strong> <i class='bi bi-box-arrow-up'></i> puis <strong>Sur l'écran d'accueil</strong>.", "info");
            }
        });
    });

    // ==================================================
    // NOTIFICATIONS PUSH
    // ==================================================
    function cleVapid() {
        const b64 = VAPID.replace(/-/g, "+").replace(/_/g, "/") + "=".repeat((4 - VAPID.length % 4) % 4);
        return Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
    }

    async function envoyerAbonnement(action, abonnement) {
        const r = await fetch(BASE + "/api/push.php", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF },
            body: JSON.stringify({ action, subscription: abonnement.toJSON() }),
        });
        if (!r.ok) throw new Error("Enregistrement refusé");
    }

    async function activerPush() {
        const permission = await Notification.requestPermission();

        if (permission !== "granted") {
            toast("Notifications bloquées", "Autorisez les notifications pour BookPro dans les réglages du navigateur.", "warning");
            return;
        }

        const reg = swReg || await navigator.serviceWorker.ready;
        const abonnement = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: cleVapid() });
        await envoyerAbonnement("subscribe", abonnement);

        toast("Notifications activées", APP === "pro"
            ? "Vous serez prévenu à chaque nouvelle demande, même application fermée."
            : "Vous serez prévenu dès qu'un professionnel répond, et la veille de vos rendez-vous.", "success");
    }

    async function desactiverPush() {
        const reg = swReg || await navigator.serviceWorker.ready;
        const abonnement = await reg.pushManager.getSubscription();

        if (abonnement) {
            await envoyerAbonnement("unsubscribe", abonnement).catch(() => {});
            await abonnement.unsubscribe();
        }

        toast("Notifications désactivées", "Vous ne recevrez plus d'alertes sur cet appareil.", "info");
    }

    async function majEtatPush() {
        const boutons = document.querySelectorAll("[data-push-toggle]");
        const etats = document.querySelectorAll("[data-push-status]");

        if (!pushDispo || !VAPID) {
            boutons.forEach((b) => b.classList.add("d-none"));
            etats.forEach((e) => e.textContent = estIOS && !estInstallee
                ? "Sur iPhone, installez d'abord l'application pour recevoir les notifications."
                : "Notifications non disponibles sur ce navigateur.");
            return;
        }

        const reg = swReg || await navigator.serviceWorker.ready;
        const abonnement = await reg.pushManager.getSubscription();
        const actif = !!abonnement && Notification.permission === "granted";

        // Réenregistre l'appareil côté serveur (ex : après une reconnexion)
        if (actif) {
            envoyerAbonnement("subscribe", abonnement).catch(() => {});
        }

        boutons.forEach((b) => {
            b.dataset.actif = actif ? "1" : "";
            b.innerHTML = actif
                ? '<i class="bi bi-bell-slash me-1"></i> Désactiver sur cet appareil'
                : '<i class="bi bi-bell me-1"></i> Activer les notifications';
            b.classList.toggle("btn-primary", !actif);
            b.classList.toggle("btn-soft", actif);
        });

        etats.forEach((e) => e.innerHTML = actif
            ? '<i class="bi bi-check-circle-fill text-success"></i> Notifications activées sur cet appareil'
            : '<i class="bi bi-bell"></i> Notifications désactivées sur cet appareil');

        // Tout est en place : la carte de présentation n'a plus d'utilité
        document.getElementById("appCard")?.classList.toggle("d-none", actif && estInstallee);
    }

    document.querySelectorAll("[data-push-toggle]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            btn.disabled = true;
            try {
                btn.dataset.actif ? await desactiverPush() : await activerPush();
            } catch (e) {
                toast("Erreur", "Impossible de modifier les notifications : " + e.message, "danger");
            }
            btn.disabled = false;
            majEtatPush();
        });
    });

    // ==================================================
    // TOASTS (empilés en bas à droite)
    // boutons : [{ label, style, onClick(toastEl) }]
    // ==================================================
    function toast(titre, texte, type = "info", boutons = [], lien = null) {
        let zone = document.querySelector(".bp-toasts");
        if (!zone) {
            zone = document.createElement("div");
            zone.className = "bp-toasts";
            document.body.appendChild(zone);
        }

        const icones = { success: "bi-check-circle-fill", danger: "bi-x-octagon-fill", warning: "bi-exclamation-triangle-fill", info: "bi-info-circle-fill", demande: "bi-bell-fill" };

        const el = document.createElement("div");
        el.className = "bp-toast bp-toast-" + type;
        el.innerHTML = `
            <i class="bi ${icones[type] || icones.info} bp-toast-icon"></i>
            <div class="flex-grow-1">
                <strong class="d-block"></strong>
                <div class="small text-muted bp-toast-texte"></div>
                ${boutons.length ? '<div class="d-flex gap-2 mt-2 bp-toast-actions"></div>' : ""}
            </div>
            <button type="button" class="btn-close" aria-label="Fermer"></button>`;

        // Textes insérés sans interprétation HTML (sauf messages internes d'aide)
        el.querySelector("strong").textContent = titre;
        type === "info" ? el.querySelector(".bp-toast-texte").innerHTML = texte : el.querySelector(".bp-toast-texte").textContent = texte;

        boutons.forEach((b) => {
            const btn = document.createElement("button");
            btn.className = "btn btn-sm " + b.style;
            btn.textContent = b.label;
            btn.addEventListener("click", () => b.onClick(el));
            el.querySelector(".bp-toast-actions").appendChild(btn);
        });

        if (lien) {
            el.querySelector("strong").style.cursor = "pointer";
            el.querySelector("strong").addEventListener("click", () => location.href = lien);
        }

        el.querySelector(".btn-close").addEventListener("click", () => el.remove());
        zone.appendChild(el);

        // Les messages avec actions restent affichés ; les autres disparaissent
        if (!boutons.length) setTimeout(() => el.remove(), 6000);

        return el;
    }

    window.bpToast = toast;

})();
