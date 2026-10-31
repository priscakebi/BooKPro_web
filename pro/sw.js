// ======================================================
// SERVICE WORKER DE L'APPLICATION PRO
// - hors ligne : dernière version vue des pages, sinon page d'attente
// - notifications push avec boutons Confirmer / Refuser
// ======================================================

const VERSION = "bookpro-pro-v1";
const BASE = self.location.pathname.replace(/\/pro\/sw\.js$/, "");

const ESSENTIELS = [
    BASE + "/assets/app.css",
    BASE + "/assets/app.js",
    BASE + "/assets/pro-app.js",
    BASE + "/assets/icons/icon-192.png",
    BASE + "/pro/offline.html",
];

self.addEventListener("install", (event) => {
    event.waitUntil(caches.open(VERSION).then((c) => c.addAll(ESSENTIELS)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys()
            .then((cles) => Promise.all(cles.filter((c) => c !== VERSION).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

// ======================================================
// RÉSEAU D'ABORD, CACHE EN SECOURS
// ======================================================
self.addEventListener("fetch", (event) => {
    const req = event.request;
    const url = new URL(req.url);

    if (req.method !== "GET" || url.origin !== self.location.origin) {
        return;
    }

    // Déconnexion : on efface les pages mémorisées (données du pro)
    if (url.pathname.endsWith("/auth/logout.php")) {
        event.waitUntil(caches.delete(VERSION));
        return;
    }

    // Les données JSON ne sont jamais servies depuis le cache
    if (url.pathname.includes("/pro/api/")) {
        return;
    }

    event.respondWith(
        fetch(req)
            .then((res) => {
                // On mémorise les pages et fichiers de l'app pro (pas les redirections)
                if (res.ok && res.type === "basic" && (url.pathname.includes("/pro/") || url.pathname.includes("/assets/"))) {
                    const copie = res.clone();
                    caches.open(VERSION).then((c) => c.put(req, copie));
                }
                return res;
            })
            .catch(() =>
                caches.match(req).then((r) => r || (req.mode === "navigate" ? caches.match(BASE + "/pro/offline.html") : undefined))
            )
    );
});

// ======================================================
// NOTIFICATION PUSH REÇUE
// ======================================================
self.addEventListener("push", (event) => {
    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.body || "",
        tag: data.tag,
        renotify: true,
        requireInteraction: !!data.actions,
        icon: BASE + "/assets/icons/icon-192.png",
        badge: BASE + "/assets/icons/icon-192.png",
        data,
        actions: data.actions
            ? [{ action: "confirmer", title: "✓ Confirmer" }, { action: "refuser", title: "✕ Refuser" }]
            : [],
    };

    event.waitUntil(
        self.registration.showNotification(data.title || "BookPro", options)
            .then(() => prevenirPages({ type: "maj" }))
    );
});

// ======================================================
// CLIC SUR LA NOTIFICATION OU SUR UN BOUTON
// ======================================================
self.addEventListener("notificationclick", (event) => {
    const n = event.notification;
    const data = n.data || {};
    n.close();

    // Bouton Confirmer / Refuser : action directe, sans ouvrir l'app
    if (event.action && data.actions && data.actions[event.action]) {
        event.waitUntil(
            fetch(BASE + "/pro/api/action.php", {
                method: "POST",
                credentials: "same-origin",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: data.id, action: event.action, jeton: data.actions[event.action] }),
            })
                .then((r) => r.json().then((j) => ({ ok: r.ok, ...j })))
                .then((res) =>
                    self.registration.showNotification(res.ok ? res.message : "Action impossible", {
                        body: res.ok ? "" : (res.erreur === "non_connecte" ? "Reconnectez-vous à l'application." : (res.message || "")),
                        tag: data.tag,
                        icon: BASE + "/assets/icons/icon-192.png",
                    })
                )
                .then(() => prevenirPages({ type: "maj" }))
                .catch(() => ouvrir(data.url))
        );
        return;
    }

    // Clic sur la notification : ouvre (ou ramène) l'app sur la bonne page
    event.waitUntil(ouvrir(data.url || BASE + "/pro/agenda.php"));
});

function ouvrir(url) {
    return self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((fenetres) => {
        const f = fenetres.find((w) => w.url.includes("/pro/"));
        return f ? f.navigate(url).then((w) => (w || f).focus()) : self.clients.openWindow(url);
    });
}

function prevenirPages(message) {
    return self.clients.matchAll({ type: "window" }).then((fenetres) => fenetres.forEach((f) => f.postMessage(message)));
}
