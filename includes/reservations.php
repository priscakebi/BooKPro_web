<?php
// ======================================================
// LOGIQUE MÉTIER DES RÉSERVATIONS CÔTÉ PRO
// (page Réservations, agenda, boutons des notifications)
// ======================================================

require_once __DIR__ . "/../config/mail.php";
require_once __DIR__ . "/webpush.php";

// Transitions autorisées au pro : action => [statut actuel, nouveau statut]
const TRANSITIONS_PRO = [
    'confirmer' => ['en_attente', 'confirmee'],
    'refuser'   => ['en_attente', 'refusee'],
    'annuler'   => ['confirmee', 'annulee'],
];

// ======================================================
// CHANGER LE STATUT D'UNE RÉSERVATION
// Renvoie [succès, message]
// ======================================================
function changer_statut_pro(PDO $pdo, $proId, $resaId, $action)
{
    if (!isset(TRANSITIONS_PRO[$action])) {
        return [false, "Action inconnue."];
    }

    [$avant, $apres] = TRANSITIONS_PRO[$action];

    // Uniquement une réservation d'un service du pro, à venir, dans le bon statut
    $stmt = $pdo->prepare("
        SELECT r.id, u.email, s.nom_service, d.date_disponible, d.heure_debut
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ? AND s.user_id = ? AND r.statut = ?
          AND d.date_disponible >= CURDATE()
    ");
    $stmt->execute([(int) $resaId, $proId, $avant]);
    $resa = $stmt->fetch();

    if (!$resa) {
        return [false, "Action impossible : la réservation a peut-être déjà été traitée."];
    }

    $pdo->prepare("UPDATE reservations SET statut = ?, annule_par = ? WHERE id = ?")
        ->execute([$apres, $apres === 'annulee' ? 'pro' : null, $resa['id']]);

    $libelles = ['confirmee' => 'confirmée', 'refusee' => 'refusée', 'annulee' => 'annulée'];

    sendMail(
        $resa['email'],
        "Votre réservation BookPro",
        "Votre réservation « {$resa['nom_service']} » du " . date_fr($resa['date_disponible'])
            . " à " . heure($resa['heure_debut']) . " a été " . $libelles[$apres] . "."
    );

    return [true, "Réservation " . $libelles[$apres] . "."];
}

// ======================================================
// JETON D'ACTION SIGNÉ (boutons des notifications)
// Lié à la réservation, à l'action et au pro : infalsifiable
// ======================================================
function jeton_action($resaId, $action, $proId)
{
    return hash_hmac('sha256', "$resaId|$action|$proId", app_secrets()['hmac_secret']);
}

function jeton_action_valide($jeton, $resaId, $action, $proId)
{
    return is_string($jeton) && hash_equals(jeton_action($resaId, $action, $proId), $jeton);
}

// ======================================================
// DEMANDES EN ATTENTE D'UN PRO (pour les notifications)
// ======================================================
function demandes_en_attente(PDO $pdo, $proId, $limit = 10)
{
    $stmt = $pdo->prepare("
        SELECT r.id, r.created_at, r.user_id AS client_id, d.date_disponible, d.heure_debut, d.heure_fin,
               s.nom_service, s.prix, u.nom AS client
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE s.user_id = ? AND r.statut = 'en_attente' AND d.date_disponible >= CURDATE()
        ORDER BY r.created_at DESC
        LIMIT " . (int) $limit
    );
    $stmt->execute([$proId]);

    return $stmt->fetchAll();
}

// Contenu d'une notification "nouvelle demande" (push et rappel in-app)
function message_demande(array $d, $proId)
{
    return [
        'tag'     => 'resa-' . $d['id'],
        'title'   => 'Nouvelle demande · ' . $d['nom_service'],
        'body'    => $d['client'] . ' · ' . jour_court($d['date_disponible']) . ' à ' . heure($d['heure_debut']) . ' · ' . prix($d['prix']),
        'url'     => BASE_URL . '/pro/agenda.php?semaine=' . lundi_de($d['date_disponible']) . '#resa-' . $d['id'],
        'id'      => (int) $d['id'],
        'actions' => [
            'confirmer' => jeton_action($d['id'], 'confirmer', $proId),
            'refuser'   => jeton_action($d['id'], 'refuser', $proId),
        ],
    ];
}

// ======================================================
// ENVOYER LA NOTIFICATION D'UNE NOUVELLE DEMANDE AU PRO
// ======================================================
function notifier_nouvelle_demande(PDO $pdo, $resaId)
{
    $stmt = $pdo->prepare("
        SELECT r.id, d.date_disponible, d.heure_debut, s.nom_service, s.prix, s.user_id AS pro_id, u.nom AS client
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$resaId]);

    if ($d = $stmt->fetch()) {
        notifier_utilisateur($pdo, $d['pro_id'], message_demande($d, $d['pro_id']));
    }
}

// Notification simple au pro (ex : annulation par le client)
function notifier_pro_info(PDO $pdo, $resaId, $titre)
{
    $stmt = $pdo->prepare("
        SELECT d.date_disponible, d.heure_debut, s.nom_service, s.user_id AS pro_id, u.nom AS client
        FROM reservations r
        JOIN disponibilites d ON r.disponibilite_id = d.id
        JOIN services s ON d.service_id = s.id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$resaId]);

    if ($d = $stmt->fetch()) {
        notifier_utilisateur($pdo, $d['pro_id'], [
            'tag'   => 'resa-' . $resaId,
            'title' => $titre,
            'body'  => $d['client'] . ' · ' . $d['nom_service'] . ' · ' . jour_court($d['date_disponible']) . ' à ' . heure($d['heure_debut']),
            'url'   => BASE_URL . '/pro/agenda.php?semaine=' . lundi_de($d['date_disponible']),
        ]);
    }
}
