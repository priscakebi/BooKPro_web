<?php
// ======================================================
// RAPPELS AUTOMATIQUES DE LA VEILLE
// À lancer toutes les heures (Planificateur de tâches / cron) :
//   php cron/rappels.php
// Options de test :
//   --maintenant        ignore l'heure de déclenchement
//   --date=AAAA-MM-JJ   jour des rendez-vous à rappeler (défaut : demain)
//
// Aucun doublon : chaque rappel est "réservé" en base avant l'envoi.
// ======================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Accès interdit.");
}

require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/mail.php";
require_once __DIR__ . "/../includes/webpush.php";

// Les rappels partent à partir de cette heure, la veille du rendez-vous
const HEURE_RAPPEL = 17;

// Pas de rappel pour une réservation faite il y a moins de 6 h (le client vient de réserver)
const DELAI_MIN_HEURES = 6;

$options = getopt('', ['maintenant', 'date:']);
$jour = $options['date'] ?? date('Y-m-d', strtotime('+1 day'));

function log_cron($message)
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

if (!isset($options['maintenant']) && (int) date('G') < HEURE_RAPPEL) {
    log_cron("Avant " . HEURE_RAPPEL . "h : rien à faire.");
    exit(0);
}

// Une seule exécution à la fois
$verrou = fopen(sys_get_temp_dir() . '/bookpro-rappels.lock', 'c');

if (!flock($verrou, LOCK_EX | LOCK_NB)) {
    log_cron("Une autre exécution est en cours.");
    exit(0);
}

// ======================================================
// 1. RAPPEL AU CLIENT (rendez-vous confirmés du jour visé)
// ======================================================

$stmt = $pdo->prepare("
    SELECT r.id, d.date_disponible, d.heure_debut, d.heure_fin, s.nom_service, s.prix,
           c.id AS client_id, c.nom AS client, c.email AS client_email,
           p.nom AS pro, p.entreprise, p.telephone AS pro_tel, p.adresse, p.code_postal, p.ville
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    JOIN users c ON r.user_id = c.id
    JOIN users p ON s.user_id = p.id
    WHERE d.date_disponible = ?
      AND r.statut = 'confirmee'
      AND r.rappel_envoye_at IS NULL
      AND p.rappels_clients = 1
      AND r.created_at < NOW() - INTERVAL " . DELAI_MIN_HEURES . " HOUR
    ORDER BY d.heure_debut
");
$stmt->execute([$jour]);

$reserver = $pdo->prepare("UPDATE reservations SET rappel_envoye_at = NOW() WHERE id = ? AND rappel_envoye_at IS NULL");
$envoyes = 0;

foreach ($stmt->fetchAll() as $r) {

    // On "réserve" le rappel : si une autre exécution l'a pris, on passe
    $reserver->execute([$r['id']]);
    if ($reserver->rowCount() !== 1) {
        continue;
    }

    $chez = $r['entreprise'] ?: $r['pro'];
    $adresse = trim($r['adresse'] . ', ' . $r['code_postal'] . ' ' . $r['ville'], ', ');

    $message = "Bonjour " . explode(' ', $r['client'])[0] . ",\n\n"
        . "Petit rappel : vous avez rendez-vous demain, " . jour_court($r['date_disponible'])
        . ", de " . heure($r['heure_debut']) . " à " . heure($r['heure_fin']) . ".\n\n"
        . "Prestation : " . $r['nom_service'] . " (" . prix($r['prix']) . ")\n"
        . "Professionnel : " . $chez . "\n"
        . ($adresse ? "Adresse : " . $adresse . "\n" : "")
        . ($r['pro_tel'] ? "Téléphone : " . $r['pro_tel'] . "\n" : "")
        . "\nUn empêchement ? Annulez depuis votre espace pour libérer le créneau :\n"
        . APP_URL . "/user/reservations.php\n\n"
        . "À demain !\nL'équipe BookPro";

    sendMail($r['client_email'], "Rappel : votre rendez-vous de demain à " . heure($r['heure_debut']), $message);

    // Et sur l'application du client
    notifier_utilisateur($pdo, $r['client_id'], [
        'tag'   => 'rappel-' . $r['id'],
        'title' => 'Demain à ' . heure($r['heure_debut']) . ' : ' . $r['nom_service'],
        'body'  => $chez . ($adresse ? ' · ' . $adresse : ''),
        'url'   => BASE_URL . '/user/reservations.php',
    ]);
    $envoyes++;
}

log_cron("$envoyes rappel" . ($envoyes > 1 ? 's' : '') . " client envoyé" . ($envoyes > 1 ? 's' : '') . " pour le $jour.");

// ======================================================
// 2. RÉSUMÉ AU PRO (une fois par jour) : RDV + demandes en attente
// ======================================================

$stmt = $pdo->prepare("
    SELECT s.user_id AS pro_id,
           SUM(r.statut = 'confirmee') AS confirmes,
           SUM(r.statut = 'en_attente') AS en_attente,
           MIN(CASE WHEN r.statut = 'confirmee' THEN d.heure_debut END) AS premier
    FROM reservations r
    JOIN disponibilites d ON r.disponibilite_id = d.id
    JOIN services s ON d.service_id = s.id
    WHERE d.date_disponible = ? AND r.statut IN ('confirmee', 'en_attente')
    GROUP BY s.user_id
");
$stmt->execute([$jour]);

$marquer = $pdo->prepare("INSERT IGNORE INTO rappels_pro (pro_id, jour) VALUES (?, ?)");
$resumes = 0;

foreach ($stmt->fetchAll() as $p) {

    $marquer->execute([$p['pro_id'], $jour]);
    if ($marquer->rowCount() !== 1) {
        continue; // déjà envoyé aujourd'hui
    }

    $lignes = [];
    if ($p['confirmes']) {
        $lignes[] = $p['confirmes'] . " RDV confirmé" . ($p['confirmes'] > 1 ? 's' : '') . ", premier à " . heure($p['premier']);
    }
    if ($p['en_attente']) {
        $lignes[] = $p['en_attente'] . " demande" . ($p['en_attente'] > 1 ? 's' : '') . " encore à confirmer";
    }

    notifier_utilisateur($pdo, $p['pro_id'], [
        'tag'   => 'demain-' . $jour,
        'title' => 'Demain, ' . jour_court($jour),
        'body'  => implode(' · ', $lignes),
        'url'   => BASE_URL . '/pro/agenda.php?semaine=' . lundi_de($jour),
    ]);

    $resumes++;
}

log_cron("$resumes résumé" . ($resumes > 1 ? 's' : '') . " envoyé" . ($resumes > 1 ? 's' : '') . " aux pros.");

flock($verrou, LOCK_UN);
